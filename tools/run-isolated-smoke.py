"""Run named HTTP smoke scripts on a disposable database, with an owned local server."""
import os
from pathlib import Path
import secrets
import socket
import sqlite3
import subprocess
import sys
import time

ROOT=Path(__file__).resolve().parents[1]
ALLOWED={'smoke_affiliate.py','smoke_blog.py','smoke_platform.py','smoke_membership.py'}
scripts=sys.argv[1:] or ['smoke_affiliate.py']
if any(script not in ALLOWED for script in scripts):raise RuntimeError('Unknown smoke script')
with socket.socket() as probe:
    if probe.connect_ex(('127.0.0.1',8081))==0:raise RuntimeError('Test port 8081 is in use. Existing processes have been left untouched.')
source=sqlite3.connect(ROOT/'writable/platform.sqlite')
target=sqlite3.connect(ROOT/'writable/platform-smoke.sqlite')
source.backup(target)
target.execute("INSERT INTO settings(key,value) VALUES('smoke_test','1') ON CONFLICT(key) DO UPDATE SET value='1'")
target.execute('DELETE FROM rate_limits')
settings=os.environ.copy()
settings['PLATFORM_TEST_PASSWORD']=secrets.token_urlsafe(24)+'Aa!1'
settings['PLATFORM_DB_PATH']=str(ROOT/'writable/platform-smoke.sqlite')
admin=target.execute("SELECT id,email FROM users WHERE role='admin' AND active=1 ORDER BY id LIMIT 1").fetchone()
settings['PLATFORM_TEST_EMAIL']=admin[1]
hashed=subprocess.run(['php','-r','echo password_hash(getenv("PLATFORM_TEST_PASSWORD"),PASSWORD_DEFAULT);'],env=settings,capture_output=True,text=True,check=True).stdout
target.execute('UPDATE users SET password_hash=? WHERE id=?',(hashed,admin[0]))
target.commit();target.close();source.close()
log=open(ROOT/'writable/logs/isolated-test-server.log','w',encoding='utf-8')
server=subprocess.Popen(['php','-d','upload_tmp_dir='+str(ROOT/'writable/upload-temp'),'-S','127.0.0.1:8081','-t',str(ROOT/'public'),str(ROOT/'tools/smoke_router.php')],cwd=ROOT,env=settings,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
try:
    for _ in range(30):
        with socket.socket() as probe:
            if probe.connect_ex(('127.0.0.1',8081))==0:break
        time.sleep(.1)
    for script in scripts:
        result=subprocess.run([sys.executable,str(ROOT/'tools'/script)],cwd=ROOT,env=settings,capture_output=True,text=True,encoding='utf-8')
        print(result.stdout)
        if result.returncode:
            print(result.stderr)
            raise RuntimeError(script+' failed')
finally:
    server.terminate();server.wait(timeout=10);log.close()
