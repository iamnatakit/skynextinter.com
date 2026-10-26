"""Affiliate/contact isolation checks. Requires the marked test DB and port 8081; never follows LINE redirects."""
import http.cookiejar
import json
import os
from pathlib import Path
import secrets
import sqlite3
import urllib.error
import urllib.parse
import urllib.request

ROOT=Path(__file__).resolve().parents[1]
db=sqlite3.connect(ROOT/'writable/platform-smoke.sqlite')
assert db.execute("SELECT value FROM settings WHERE key='smoke_test'").fetchone()[0]=='1'
BASE='http://127.0.0.1:8081/'
checks=[]
password=secrets.token_urlsafe(20)+'Aa!1'
nonce=secrets.token_hex(5)

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs):return None

class Client:
    def __init__(self):
        self.jar=http.cookiejar.CookieJar()
        self.opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(self.jar))
        self.no_redirect=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(self.jar),NoRedirect())
    def get(self,path,follow=True):return self.request(path,follow=follow)
    def request(self,path,data=None,headers=None,follow=True):
        request=urllib.request.Request(BASE+path,data=data,headers=headers or {})
        try:
            response=(self.opener if follow else self.no_redirect).open(request,timeout=20)
            assert urllib.parse.urlsplit(response.url).port==8081,'A test escaped the local server'
            return response.status,response.read().decode('utf-8'),response.headers
        except urllib.error.HTTPError as error:
            return error.code,error.read().decode('utf-8'),error.headers
    def post(self,path,data):
        bootstrap=json.loads(self.get('api/platform/bootstrap')[1])
        return self.request(path,urllib.parse.urlencode(data).encode(),{'X-CSRF-TOKEN':bootstrap['csrf'],'Content-Type':'application/x-www-form-urlencoded'})
    def register(self,key,name):
        email=key+'-'+nonce+'@example.test'
        self.post('register',{'name':name,'email':email,'phone':'','password':password,'consent':'1'})
        return db.execute('SELECT id,referral_code FROM users WHERE email=?',(email,)).fetchone()

def check(name,result):
    assert result,name
    checks.append(name)

anonymous=Client()
check('affiliate dashboard requires authentication',anonymous.get('affiliate',False)[0]==302)
one=Client();one_id,one_code=one.register('one','PRIVATE ACCOUNT NAME A')
two=Client();two_id,two_code=two.register('two','PRIVATE ACCOUNT NAME B')
check('private dashboard exposes own promotion links','p/'+one_code in one.get('affiliate')[1])
check('unpublished profile does not expose account name','PRIVATE ACCOUNT NAME B' not in anonymous.get('p/'+two_code)[1])
profile={'public_name':'Public Member A','line_url':'https://lin.ee/AffiliateTestA','affiliate_headline':'Affiliate test introduction','affiliate_bio':'Public bio <script>alert("xss")</script>','affiliate_public':'1'}
one.post('affiliate/profile',profile)
two.post('affiliate/profile',dict(profile,public_name='Public Member B',line_url='https://line.me/ti/p/AffiliateTestB'))
visitor=Client();body=visitor.get('p/'+one_code)[1]
check('promo page shows public member and join link','Public Member A' in body and 'r/'+one_code in body)
check('public bio is escaped','&lt;script&gt;' in body and '<script>alert(' not in body)
check('home contact retains original affiliate','contact-us/'+one_code in visitor.get('')[1])
check('blog contact retains original affiliate','contact-us/'+one_code in visitor.get('blog')[1])
status,_,headers=visitor.get('contact-us',False)
check('contact goes to member A LINE',status==302 and headers['Location']==profile['line_url'])
check('contact redirect disables caching','no-store' in headers.get('Cache-Control',''))
for cookie in list(visitor.jar):
    if cookie.name not in ['sn_ref','sn_affiliate']:visitor.jar.clear(cookie.domain,cookie.path,cookie.name)
check('opaque cookie restores contact after session loss',visitor.get('contact-us',False)[2]['Location']==profile['line_url'])
visitor.get('p/'+two_code)
check('new explicit affiliate link selects member B',visitor.get('contact-us',False)[2]['Location']=='https://line.me/ti/p/AffiliateTestB')
two.get('p/'+one_code)
check('attribution wins over different signed in account',two.get('contact-us',False)[2]['Location']==profile['line_url'])
child=Client();child.get('r/'+one_code)
child_id,child_code=child.register('child','Referred Child')
check('signup persists correct sponsor',db.execute('SELECT sponsor_id FROM users WHERE id=?',(child_id,)).fetchone()[0]==one_id)
check('contact keeps sponsor context after registration',child.get('contact-us',False)[2]['Location']==profile['line_url'])
one.post('affiliate/network/'+str(child_id),{'stage':'contacted','note':'Private network note'})
check('owner can manage descendant follow-up',db.execute('SELECT note FROM affiliate_notes WHERE owner_id=? AND member_id=?',(one_id,child_id)).fetchone()[0]=='Private network note')
two.post('affiliate/network/'+str(child_id),{'stage':'contacted','note':'Not my network'})
check('another member cannot edit network notes',not db.execute('SELECT note FROM affiliate_notes WHERE owner_id=? AND member_id=?',(two_id,child_id)).fetchone())
check('network note does not appear on public promo','Private network note' not in anonymous.get('p/'+one_code)[1])
one.post('affiliate/profile',dict(profile,line_url='https://evil.test/redirect'))
check('external redirect injection is rejected',db.execute('SELECT line_url FROM users WHERE id=?',(one_id,)).fetchone()[0]==profile['line_url'])
one.post('affiliate/profile',dict(profile,affiliate_public='0'))
check('unpublishing disables member LINE redirects',visitor.get('contact-us/'+one_code,False)[0]==200)
check('unpublishing hides public bio','Public bio' not in anonymous.get('p/'+one_code)[1])
admin=Client();admin_email=db.execute("SELECT email FROM users WHERE role='admin' AND active=1 ORDER BY id LIMIT 1").fetchone()[0]
admin.post('login',{'email':admin_email,'password':os.environ['PLATFORM_TEST_PASSWORD']})
check('admin network management renders','Referred Child' in admin.get('admin/affiliates?edit='+str(one_id))[1])
admin.post('admin/affiliates/profile/'+str(one_id),profile)
check('admin can manage member profile',visitor.get('contact-us/'+one_code,False)[2]['Location']==profile['line_url'])
db.execute('UPDATE users SET active=0 WHERE id=?',(one_id,));db.commit()
check('suspended member promotion is unavailable',anonymous.get('p/'+one_code)[0]==404)
check('suspended member contact is unavailable',anonymous.get('contact-us/'+one_code,False)[0]==404)
check('csrf protects profile changes',two.request('affiliate/profile',b'public_name=Forged')[0]==403)
check('contact clicks are tracked without visitor identifiers',db.execute("SELECT count(*) FROM affiliate_events WHERE sponsor_id=? AND kind='contact'",(one_id,)).fetchone()[0]>0)
print(json.dumps({'checks_passed':len(checks),'checks':checks,'LINE_network_requests':0},ensure_ascii=True))
