"""Membership HTTP regression checks; only runs against the isolated marked DB."""
import http.cookiejar
import json
import os
from pathlib import Path
import secrets
import sqlite3
import subprocess
import urllib.error
import urllib.parse
import urllib.request

ROOT=Path(__file__).resolve().parents[1]
db=sqlite3.connect(ROOT/'writable/platform-smoke.sqlite')
assert db.execute("SELECT value FROM settings WHERE key='smoke_test'").fetchone()[0]=='1'
BASE='http://127.0.0.1:8081/'
checks=[]
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs): return None
class Client:
    def __init__(self):
        jar=http.cookiejar.CookieJar()
        self.opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(jar),NoRedirect())
    def get(self,path,data=None,headers=None):
        request=urllib.request.Request(BASE+path,data=data,headers=headers or {})
        try:
            r=self.opener.open(request,timeout=20)
            return r.status,r.read().decode('utf-8'),r.headers
        except urllib.error.HTTPError as e: return e.code,e.read().decode('utf-8'),e.headers
    def post(self,path,data):
        token=json.loads(self.get('api/platform/bootstrap')[1])['csrf']
        return self.get(path,urllib.parse.urlencode(data).encode(),{'X-CSRF-TOKEN':token,'Content-Type':'application/x-www-form-urlencoded'})
    def login(self,email,password): return self.post('login',{'email':email,'password':password})
def check(name,result):
    assert result,name
    checks.append(name)

password=secrets.token_urlsafe(24)+'Aa!1'
env=os.environ.copy();env['FIXTURE_PASSWORD']=password
hashed=subprocess.run(['php','-r','echo password_hash(getenv("FIXTURE_PASSWORD"),PASSWORD_DEFAULT);'],env=env,capture_output=True,text=True,check=True).stdout
nonce=secrets.token_hex(5)
email='membership-'+nonce+'@example.test'
uid=db.execute("INSERT INTO users(name,email,password_hash,role,referral_code,created_at,affiliate_public,public_name,line_url) VALUES(?,?,?,'affiliate',?,'2020-01-01 00:00:00',1,'Membership Fixture','https://lin.ee/FixtureOnly')",('Membership Fixture',email,hashed,nonce)).lastrowid
db.execute("INSERT INTO settings(key,value) VALUES('platform_free_roots','[]') ON CONFLICT(key) DO UPDATE SET value='[]'")
db.commit()
user=Client();anonymous=Client()
check('billing requires login',anonymous.get('platform/billing')[0]==302)
login=user.login(email,password)
print('Fixture login:',login[0],login[2].get('Location',''))
check('fixture login succeeds',login[0] in (302,303))
for path in ['admin','admin/dashboard','affiliate','affiliate/profile','affiliate/network']:
    status,body,headers=user.get(path)
    check('expired access blocked: '+path,status==302 and headers.get('Location','').endswith('/platform/billing'))
check('expired account remains accessible',user.get('account')[0]==200)
check('expired billing remains accessible',user.get('platform/billing')[0]==200)
check('expired public website paused','เว็บไซต์โปรไฟล์นี้อยู่ระหว่างต่ออายุ' in anonymous.get('p/'+nonce)[1])
before=db.execute('SELECT public_name FROM users WHERE id=?',(uid,)).fetchone()[0]
status,_,headers=user.post('affiliate/profile',{'public_name':'MUST NOT CHANGE'})
check('expired mutation blocked',status in (302,303) and headers.get('Location','').endswith('/platform/billing') and db.execute('SELECT public_name FROM users WHERE id=?',(uid,)).fetchone()[0]==before)
check('only one expiry email queued',db.execute('SELECT COUNT(*) FROM platform_emails WHERE user_id=?',(uid,)).fetchone()[0]==1)
admin=Client()
check('admin login succeeds',admin.login(os.environ['PLATFORM_TEST_EMAIL'],os.environ['PLATFORM_TEST_PASSWORD'])[0] in (302,303))
body=admin.get('admin/user_management')[1]
check('all six grouped menu labels',all(label in body for label in ['เครือข่าย Affiliate','โปรไฟล์ Affiliate','จัดการผู้ใช้และ Role','จัดการเมนูและสิทธิ์','ผู้ใช้ใต้สายงาน','ผู้ใช้ต่างสายงาน']))
check('non-admin cannot open membership admin',user.get('admin/user_management')[0]==302)
db.execute("INSERT INTO settings(key,value) VALUES('platform_free_roots',?) ON CONFLICT(key) DO UPDATE SET value=excluded.value",(json.dumps([uid]),));db.commit()
check('Affiliate trial is not bypassed by root grouping',user.get('affiliate')[0]==302)
db.execute("UPDATE users SET role='downline_team' WHERE id=?",(uid,));db.commit()
check('downline team restores affiliate access',user.get('affiliate')[0]==200)
check('team still cannot manage billing users',user.get('admin/user_management')[0]==403)
check('team public website restored','เว็บไซต์โปรไฟล์นี้อยู่ระหว่างต่ออายุ' not in anonymous.get('p/'+nonce)[1])
check('new roles appear in Admin user form',all(label in admin.get('admin/users')[1] for label in ['ทีมงานใต้สายงาน','ทีมงานต่างสายงาน']))
admin.post('admin/products/save/0',{'name':'PV fixture','sku':'PV-'+nonce,'price':'100.00','pv':'50','stock':'10','category':'Packet','description':'Isolated fixture','image':'','active':'1'})
product=db.execute('SELECT id,pv FROM products WHERE sku=?',('PV-'+nonce,)).fetchone()
check('Admin can set product PV',product is not None and product[1]==50)
db.execute("UPDATE users SET role='affiliate' WHERE id=?",(uid,));db.commit()
user.post('cart',{'mode':'add','product_id':str(product[0]),'quantity':'2','pv':'999999'})
body=user.get('checkout')[1]
import re
key=re.search(r'name="idempotency_key" value="([a-f0-9]{32})"',body).group(1)
check('expired Affiliate can purchase and sees 100 PV','100 PV' in body)
user.post('checkout',{'idempotency_key':key,'recipient':'Fixture buyer','phone':'0812345678','address':'Test address','coupon':'','pv':'999999'})
order=db.execute('SELECT id,total_satang FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 1',(uid,)).fetchone()
check('checkout snapshots server PV and quantity',db.execute('SELECT SUM(pv*quantity) FROM order_items WHERE order_id=?',(order[0],)).fetchone()[0]==100)
check('checkout does not immediately grant team role',db.execute('SELECT role FROM users WHERE id=?',(uid,)).fetchone()[0]=='affiliate')
db.execute('UPDATE products SET pv=0 WHERE id=?',(product[0],))
payment=db.execute("INSERT INTO payments(order_id,amount_satang,reference,slip,slip_hash,mime,status,created_at) VALUES(?,?,'fixture','fixture.png',?,'image/png','pending',datetime('now'))",(order[0],order[1],'fixture-'+nonce)).lastrowid
db.execute("UPDATE orders SET status='payment_review' WHERE id=?",(order[0],));db.commit()
admin.post('admin/payments/approve/'+str(payment),{'note':'Isolated bank confirmation fixture'})
check('confirmed 100 PV promotes to downline team',db.execute('SELECT role FROM users WHERE id=?',(uid,)).fetchone()[0]=='downline_team')
check('team can use business management',user.get('admin/products')[0]==200)
check('team cannot access Admin role management',user.get('admin/users')[0]==403)
legacy=Client();legacy.login(os.environ['PLATFORM_TEST_EMAIL'],'admin123')
check('legacy default password does not bypass admin authentication',legacy.get('admin/user_management')[0]==302)
print(json.dumps({'passed':len(checks),'checks':checks},ensure_ascii=False,indent=2))

