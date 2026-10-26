"""Exercise the blog workflow on the dedicated, explicitly marked test database."""
import http.cookiejar
import base64
import json
import os
import re
from pathlib import Path
import sqlite3
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
db = sqlite3.connect(ROOT / 'writable/platform-smoke.sqlite')
assert db.execute("SELECT value FROM settings WHERE key='smoke_test'").fetchone()[0] == '1'
BASE = 'http://127.0.0.1:8081/'
admin_id, admin_email = db.execute("SELECT id,email FROM users WHERE role='admin' AND active=1 ORDER BY id LIMIT 1").fetchone()
checks = []

class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def get(self, path):
        return self.request(path)
    def request(self, path, data=None, headers=None):
        try:
            response = self.opener.open(urllib.request.Request(BASE+path, data=data, headers=headers or {}), timeout=20)
            assert urllib.parse.urlsplit(response.url).port == 8081
            return response.status, response.read().decode('utf-8'), response.url
        except urllib.error.HTTPError as error:
            return error.code, error.read().decode('utf-8'), error.url
    def post(self, path, data):
        boot = json.loads(self.get('api/platform/bootstrap')[1])
        return self.request(path, urllib.parse.urlencode(data).encode(), {'X-CSRF-TOKEN':boot['csrf'], 'Content-Type':'application/x-www-form-urlencoded'})

def check(name, result):
    assert result, name
    checks.append(name)

anon = Client()
admin = Client()
check('private management requires login', '/login' in anon.get('admin/blog')[2])
admin.post('login', {'email':admin_email, 'password':os.environ['PLATFORM_TEST_PASSWORD']})
check('admin editor opens', 'name="body"' in admin.get('admin/blog')[1])
article = {'title':'Blog smoke test', 'slug':'blog-smoke-test', 'excerpt':'HTTP article summary', 'body':'Original body text for the test article.\n\n<script>alert("xss")</script>', 'category':'Test', 'cover_image':'', 'status':'draft'}
admin.post('admin/blog/save/0', article)
article_id = db.execute('SELECT id FROM blog WHERE slug=?', (article['slug'],)).fetchone()[0]
check('draft is private', anon.get('blog/'+article['slug'])[0] == 404)
check('draft absent from index', 'Blog smoke test' not in anon.get('blog')[1])
article['status'] = 'published'
admin.post('admin/blog/save/'+str(article_id), article)
body = anon.get('blog/'+article['slug'])[1]
check('published article loads', 'Original body text' in body)
check('article HTML is escaped', '&lt;script&gt;' in body and '<script>alert(' not in body)
check('new article appears alongside starters', anon.get('blog')[1].count('class="sn-blog-card"') == 9)
article['title'] = 'Edited blog smoke test'
admin.post('admin/blog/save/'+str(article_id), article)
check('edit updates article', 'Edited blog smoke test' in anon.get('blog/'+article['slug'])[1])
admin.post('admin/blog/archive/'+str(article_id), {})
check('archiving removes public article', anon.get('blog/'+article['slug'])[0] == 404)
check('archiving retains eight original articles', anon.get('blog')[1].count('class="sn-blog-card"') == 8)
admin.post('admin/blog/save/'+str(article_id), article)
check('archived article can be restored', anon.get('blog/'+article['slug'])[0] == 200)
check('category filter works', anon.get('blog?category=Test')[1].count('class="sn-blog-card"') == 1)
check('search works', anon.get('blog?q=Edited')[1].count('class="sn-blog-card"') == 1)
check('csrf protects article creation', admin.request('admin/blog/save/0', b'title=bad')[0] == 403)
boundary = 'SkyNextBlogImageBoundary'
boot = json.loads(admin.get('api/platform/bootstrap')[1])
parts = [f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode() for key,value in article.items()]
image = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="photo"; filename="cover.png"\r\nContent-Type: image/png\r\n\r\n'.encode()+image+b'\r\n')
parts.append(f'--{boundary}--\r\n'.encode())
admin.request('admin/blog/save/'+str(article_id), b''.join(parts), {'Content-Type':'multipart/form-data; boundary='+boundary,'X-CSRF-TOKEN':boot['csrf']})
cover = db.execute('SELECT cover_image FROM blog WHERE id=?',(article_id,)).fetchone()[0]
check('blog cover upload validates and stores image', bool(re.fullmatch(r'platform/uploads/[a-f0-9]{32}\.png',cover)))
check('uploaded cover appears on article', cover in anon.get('blog/'+article['slug'])[1])
production = sqlite3.connect(ROOT/'writable/platform.sqlite')
assert not production.execute('SELECT id FROM blog WHERE cover_image=?',(cover,)).fetchone()
cover_file = (ROOT/'public'/cover).resolve()
assert cover_file.parent == (ROOT/'public/platform/uploads').resolve()
cover_file.unlink()
db.execute("UPDATE users SET role='sales' WHERE id=?",(admin_id,))
db.commit()
check('sales cannot access blog management', admin.get('admin/blog')[0] == 403)
check('sales cannot edit articles', admin.post('admin/blog/save/'+str(article_id), article)[0] == 403)
db.execute("UPDATE users SET role='manager' WHERE id=?",(admin_id,))
db.commit()
check('manager can access blog management', admin.get('admin/blog')[0] == 200)
print(json.dumps({'checks_passed':len(checks),'checks':checks,'database':'isolated smoke database'}, ensure_ascii=True))
