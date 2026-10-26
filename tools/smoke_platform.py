"""Run the purchase/CRM/security flow against an explicitly isolated local smoke database."""
import base64, http.cookiejar, json, os, re, sqlite3, urllib.error, urllib.parse, urllib.request
from pathlib import Path

base = 'http://127.0.0.1:8081/'
db_path = Path(__file__).resolve().parents[1] / 'writable/platform-smoke.sqlite'
db = sqlite3.connect(db_path)
db.row_factory = sqlite3.Row
assert db.execute("SELECT value FROM settings WHERE key='smoke_test'").fetchone()[0] == '1'
checks=[]

class Client:
    def __init__(self):
        self.jar=http.cookiejar.CookieJar()
        self.op = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(self.jar))
    def request(self,path,data=None,headers=None):
        req = urllib.request.Request(base + path.lstrip('/'), data=data,headers=headers or {})
        try:
            r = self.op.open(req,timeout=30)
            assert urllib.parse.urlsplit(r.url).port == 8081, 'Redirect escaped test server'
            return r.status,r.read().decode('utf-8',errors='replace'),r.url
        except urllib.error.HTTPError as e:
            return e.code,e.read().decode('utf-8'),e.url
    def get(self,path): return self.request(path)
    def post(self,path,data):
        boot=json.loads(self.get('api/platform/bootstrap')[1])
        return self.request(path,urllib.parse.urlencode(data).encode(),{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':boot['csrf']})
    def login(self,email,password):
        result=self.post('login',{'email':email,'password':password})
        assert result[0]==200 and '/login' not in result[2], result[1][:500]

def check(name,ok):
    assert ok,name
    checks.append(name)

admin=Client(); anonymous=Client()
check('anonymous admin redirects to login','/login' in anonymous.get('admin')[2])
check('CSRF rejects mutation without token',anonymous.request('cart',b'product_id=1&quantity=1')[0]==403)
admin.login(os.environ.get('PLATFORM_TEST_EMAIL','admin@skynext.local'),os.environ['PLATFORM_TEST_PASSWORD'])
for module in ['dashboard','products','orders','payments','promotions','users','customers','leads','tasks','conversations','ai','affiliates','analytics','integrations','settings','audit']:
    status,body,_=admin.get('admin/'+module)
    check('admin page '+module,status==200 and 'SKYNEXT BUSINESS PLATFORM' in body)
check('test server uses isolated DB',db.execute('SELECT COUNT(*) FROM audit').fetchone()[0]>=1)
admin.post('admin/products/save/0',{'name':'Smoke Test Product','sku':'SMOKE-TEST','price':'149.95','stock':'6','category':'Test','description':'Transactional test','image':'','active':'1'})
product=db.execute("SELECT * FROM products WHERE sku='SMOKE-TEST'").fetchone()
check('product management saved in isolated DB',product is not None and product['price_satang']==14995)
photo_boot=json.loads(admin.get('api/platform/bootstrap')[1]);photo_boundary='ProductPhotoBoundary';photo_parts=[]
for k,v in {'name':'Smoke Test Product','sku':'SMOKE-TEST','price':'149.95','stock':'6','category':'Test','description':'Transactional test','image':'','active':'1'}.items():
    photo_parts.append(f'--{photo_boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
photo_bytes=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
photo_parts.append(f'--{photo_boundary}\r\nContent-Disposition: form-data; name="photo"; filename="photo.png"\r\nContent-Type: image/png\r\n\r\n'.encode()+photo_bytes+b'\r\n')
photo_parts.append(f'--{photo_boundary}--\r\n'.encode())
admin.request('admin/products/save/'+str(product['id']),b''.join(photo_parts),{'Content-Type':'multipart/form-data; boundary='+photo_boundary,'X-CSRF-TOKEN':photo_boot['csrf']})
photo_path=db.execute('SELECT image FROM products WHERE id=?',(product['id'],)).fetchone()[0]
check('product image uploaded safely',bool(re.fullmatch(r'platform/uploads/[a-f0-9]{32}\.png',photo_path)))
check('uploaded product image served',admin.get(photo_path)[0]==200)
admin.post('admin/promotions/save/0',{'code':'SMOKE10','kind':'percent','value':'10','minimum':'100','starts_at':'2020-01-01T00:00','ends_at':'2099-01-01T00:00','usage_limit':'1','active':'1'})
check('promotion creation',db.execute("SELECT value FROM promotions WHERE code='SMOKE10'").fetchone()[0]==1000)
ref_row=db.execute("SELECT id,referral_code FROM users WHERE role='admin' AND active=1 ORDER BY id LIMIT 1").fetchone()
ref=ref_row["referral_code"]
ref_clicks_before=db.execute("SELECT COUNT(*) FROM referral_clicks WHERE sponsor_id=?",(ref_row["id"],)).fetchone()[0]
buyer=Client();buyer.get('r/'+ref)
for cookie in list(buyer.jar):
    if cookie.name=='ci_session': buyer.jar.clear(cookie.domain,cookie.path,cookie.name)
buyer.post('register',{'name':'Smoke Buyer','email':'smoke-buyer@example.test','password':'Smoke-Password-123!','phone':'0812345678','consent':'1'})
buyer_row=db.execute("SELECT * FROM users WHERE email='smoke-buyer@example.test'").fetchone()
check('referral registration and sponsor',buyer_row is not None and buyer_row['sponsor_id']==ref_row['id'])
check('affiliate click counted',db.execute('SELECT COUNT(*) FROM referral_clicks WHERE sponsor_id=?',(ref_row['id'],)).fetchone()[0]==ref_clicks_before+1)
check('member cannot access admin',buyer.get('admin/users')[0]==403)
buyer.post('cart',{'product_id':str(product['id']),'quantity':'2','mode':'add'})
checkout=buyer.get('checkout')
key=re.search(r'name="idempotency_key" value="([a-f0-9]+)"',checkout[1])[1]
order_data={'recipient':'Smoke Buyer','phone':'0812345678','address':'Test address 123 Bangkok','coupon':'SMOKE10','idempotency_key':key,'total_satang':'1'}
buyer.post('checkout',order_data);buyer.post('checkout',order_data)
order=db.execute('SELECT * FROM orders WHERE user_id=?',(buyer_row['id'],)).fetchone()
check('server recomputes total with promo',order['total_satang']==26991)
check('duplicate checkout is idempotent',db.execute('SELECT COUNT(*) FROM orders WHERE user_id=?',(buyer_row['id'],)).fetchone()[0]==1)
check('stock reserved once',db.execute('SELECT stock FROM products WHERE id=?',(product['id'],)).fetchone()[0]==4)
check('order page correct',order['number'] in buyer.get('orders/'+str(order['id']))[1])
other=Client();other.post('register',{'name':'Other Buyer','email':'smoke-other@example.test','password':'Smoke-Password-123!','phone':'0812345678','consent':'1'})
check('order ownership isolation',other.get('orders/'+str(order['id']))[0]==404)
# Exercise actual image upload, private storage and approval (no real money transferred).
boot=json.loads(buyer.get('api/platform/bootstrap')[1]);boundary='SmokeBoundary1234';parts=[]
for key,value in {'amount':'269.91','reference':'SMOKE-BANK-123'}.items():
    parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="slip"; filename="proof.png"\r\nContent-Type: image/png\r\n\r\n'.encode()+png+b'\r\n')
parts.append(f'--{boundary}--\r\n'.encode())
uploaded=buyer.request('orders/'+str(order['id'])+'/payment',b''.join(parts),{'Content-Type':'multipart/form-data; boundary='+boundary,'X-CSRF-TOKEN':boot['csrf']})
payment=db.execute('SELECT * FROM payments WHERE order_id=?',(order['id'],)).fetchone()
if payment is None:
    print('Upload response:',uploaded[0],uploaded[2],re.findall(r'<title>(.*?)</title>',uploaded[1],re.S),re.findall(r'<div class="notice error"[^>]*>(.*?)</div>',uploaded[1],re.S))
    print(uploaded[1][:1800])
check('slip uploaded and pending review',payment is not None and payment['status']=='pending')
check('member cannot access private slips',buyer.get('admin/payments/slip/'+str(payment['id']))[0]==403)
admin.post('admin/payments/approve/'+str(payment['id']),{'note':'Smoke test bank verification'})
check('payment approved',db.execute('SELECT status FROM orders WHERE id=?',(order['id'],)).fetchone()[0]=='paid')
for state,note in [('processing','Test preparation'),('shipped','SMOKE-TRACK-123'),('completed','Test delivery')]:
    admin.post('admin/orders/status/'+str(order['id']),{'status':state,'note':note})
check('order fulfilment transitions',db.execute('SELECT status FROM orders WHERE id=?',(order['id'],)).fetchone()[0]=='completed')
chat=Client();chat.post('api/platform/chat',{'message':'Smoke Test Product','consent':'1'})
transcript=json.loads(chat.get('api/platform/chat')[1])
check('chatbot uses live catalog',any('149.95' in m['body'] for m in transcript['messages']))
chat.post('api/platform/lead',{'name':'Smoke Lead','contact':'lead@example.test','consent':'1'})
lead=db.execute("SELECT * FROM leads WHERE contact='lead@example.test'").fetchone()
check('chat creates consented lead',lead is not None and lead['consent_at'] is not None)
admin.post('admin/leads/save/'+str(lead['id']),{'name':'Smoke Lead','contact':'lead@example.test','source':'web:chat','stage':'qualified','score':'70','owner_id':'1','notes':'Follow up requested'})
check('CRM lead update',db.execute('SELECT stage FROM leads WHERE id=?',(lead['id'],)).fetchone()[0]=='qualified')
admin.post('admin/tasks/save/0',{'title':'Call smoke lead','lead_id':str(lead['id']),'owner_id':'1','due_at':'2099-01-01T12:00','status':'open'})
check('CRM follow-up task',db.execute("SELECT COUNT(*) FROM tasks WHERE title='Call smoke lead'").fetchone()[0]==1)
chat.post('api/platform/chat',{'message':'แอดมิน','consent':'1'})
conv=db.execute('SELECT * FROM conversations WHERE lead_id=?',(lead['id'],)).fetchone()
check('human handoff',conv['mode']=='human')
admin.post('admin/conversations/reply/'+str(conv['id']),{'body':'Smoke human reply'})
check('staff reply appears only in owned conversation',any(m['body']=='Smoke human reply' for m in json.loads(chat.get('api/platform/chat')[1])['messages']))
check('separate visitor cannot see chat',not json.loads(anonymous.get('api/platform/chat')[1])['messages'])
before=db.execute('SELECT COUNT(*) FROM visits').fetchone()[0]
chat.post('api/platform/track',{'path':'/shop','consent':'0'})
check('analytics requires consent',db.execute('SELECT COUNT(*) FROM visits').fetchone()[0]==before)
chat.post('api/platform/track',{'path':'/shop?secret=do-not-store','referrer':'https://example.test/private?key=abc','consent':'1','source':'test'})
visit=db.execute('SELECT * FROM visits ORDER BY id DESC LIMIT 1').fetchone()
check('analytics strips query and referrer path',visit['path']=='/shop' and visit['referrer']=='example.test')
for provider in ['line','facebook','stripe','opn']:
    status,_,_=anonymous.request('hooks/'+provider,b'{}',{'Content-Type':'application/json'})
    check('unconfigured hook fails closed '+provider,status>=400)
for module in ['orders','payments','leads','tasks','conversations','analytics','audit']:
    check('populated admin page '+module,admin.get('admin/'+module)[0]==200)
for module,record_id in [('products',product['id']),('users',buyer_row['id']),('orders',order['id']),('leads',lead['id']),('conversations',conv['id'])]:
    check('detail form '+module,admin.get('admin/'+module+'?edit='+str(record_id))[0]==200)
# Staff permission boundaries are tested over HTTP, not only through navigation visibility.
admin.post('admin/users/save/0',{'name':'Smoke Sales','email':'smoke-sales@example.test','phone':'','password':'Smoke-Password-123!','role':'sales','active':'1'})
sales=Client();sales.login('smoke-sales@example.test','Smoke-Password-123!')
check('sales can view CRM',sales.get('admin/leads')[0]==200)
check('sales cannot view payments',sales.get('admin/payments')[0]==403)
check('sales cannot mutate users',sales.post('admin/users/save/'+str(buyer_row['id']),{'role':'admin'})[0]==403)
second=Client();second.login('smoke-buyer@example.test','Smoke-Password-123!')
buyer.post('account',{'name':'Smoke Buyer','phone':'0812345678','current_password':'Smoke-Password-123!','new_password':'New-Smoke-Password-123!'})
check('password change invalidates other sessions','/login' in second.get('account')[2])
check('password change preserves own session','/account' in buyer.get('account')[2])
# Remove only the private proof image created by this isolated test, preserving all production records.
production=sqlite3.connect(db_path.parent/'platform.sqlite')
assert not production.execute('SELECT id FROM payments WHERE slip=?',(payment['slip'],)).fetchone()
proof=(db_path.parent/'slips'/payment['slip']).resolve()
assert proof.parent==(db_path.parent/'slips').resolve() and re.fullmatch(r'[a-f0-9]{32}\.png',proof.name)
proof.unlink(missing_ok=True)
photo=(db_path.parents[1]/'public'/photo_path).resolve()
assert photo.parent==(db_path.parents[1]/'public/platform/uploads').resolve() and re.fullmatch(r'[a-f0-9]{32}\.png',photo.name)
assert not production.execute('SELECT id FROM products WHERE image=?',(photo_path,)).fetchone()
photo.unlink(missing_ok=True)
report={'checks_passed':len(checks),'checks':checks,'database':str(db_path),'live_services_tested':False}
(db_path.parent/'platform-smoke-report.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps(report,ensure_ascii=True))
