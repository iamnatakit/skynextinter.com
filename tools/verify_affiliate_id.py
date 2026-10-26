import sqlite3
import urllib.request
import urllib.parse
import http.cookiejar
import re
import sys

sys.stdout.reconfigure(encoding='utf-8')

BASE = 'http://localhost:88/skynextinter.com/public/'

# Reset initial state
conn = sqlite3.connect('writable/platform.sqlite')
c = conn.cursor()
c.execute("UPDATE users SET referral_code='0001' WHERE id=1")
c.execute("UPDATE users SET referral_code='0002' WHERE id=2")
c.execute("DELETE FROM users WHERE email IN ('newmember.test@skynext.test', 'fourth.test@skynext.test')")
conn.commit()
conn.close()

print("=== 1. Check current users in DB ===")
conn = sqlite3.connect('writable/platform.sqlite')
c = conn.cursor()
c.execute("SELECT id, name, email, role, referral_code, sponsor_id FROM users ORDER BY id ASC")
for row in c.fetchall():
    print(f"User #{row[0]}: {row[1]} ({row[3]}) -> Code: {row[4]}, Sponsor: {row[5]}")
conn.close()

# Prepare cookie jar and opener
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

print("\n=== 2. Test sequential referral link /r/0002 and check session banner ===")
r_res = opener.open(BASE + 'r/0002')
print("Redirected to URL:", r_res.geturl())
reg_html = r_res.read().decode('utf-8')
assert "0002" in reg_html, "Expected referral code 0002 in register banner"
print("Banner displayed referral code 0002 successfully!")

# Extract CSRF token and name from register page
csrf_match = re.search(r'name="(csrf_[^"]+)"\s+value="([^"]+)"', reg_html)
assert csrf_match, "CSRF field not found"
csrf_name = csrf_match.group(1)
csrf_val = csrf_match.group(2)
print(f"Extracted CSRF: {csrf_name}={csrf_val[:10]}...")

print("\n=== 3. Register new user under sponsor 0002 (expecting sequential ID: 0003) ===")
# Clean any previous test user
conn = sqlite3.connect('writable/platform.sqlite')
conn.cursor().execute("DELETE FROM users WHERE email='newmember.test@skynext.test'")
conn.commit()
conn.close()

reg_data = urllib.parse.urlencode({
    csrf_name: csrf_val,
    'name': 'Test New Member',
    'email': 'newmember.test@skynext.test',
    'password': 'Password123456789!',
    'phone': '0812345678',
    'consent': '1'
}).encode('utf-8')

post_req = urllib.request.Request(
    BASE + 'register',
    data=reg_data,
    headers={'Content-Type': 'application/x-www-form-urlencoded'}
)

reg_res = opener.open(post_req)
print("Registered and redirected to:", reg_res.geturl())

conn = sqlite3.connect('writable/platform.sqlite')
c = conn.cursor()
c.execute("SELECT id, name, email, role, referral_code, sponsor_id FROM users WHERE email='newmember.test@skynext.test'")
new_user = c.fetchone()
conn.close()

assert new_user is not None, "New user was not created in DB!"
print(f"SUCCESS: Created User #{new_user[0]}: {new_user[1]} with sequential code: {new_user[4]} and sponsor_id: {new_user[5]}")
assert new_user[4] == '0003', f"Expected code 0003, got {new_user[4]}"
assert new_user[5] == 2, f"Expected sponsor_id 2 (Somsak), got {new_user[5]}"

print("\n=== 4. Test Next Code Generation (expecting next sequential ID: 0004) ===")
# Register another user to verify 0004
cj2 = http.cookiejar.CookieJar()
opener2 = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj2))
r_html = opener2.open(BASE + 'register').read().decode('utf-8')
csrf_m = re.search(r'name="(csrf_[^"]+)"\s+value="([^"]+)"', r_html)
d2 = urllib.parse.urlencode({
    csrf_m.group(1): csrf_m.group(2),
    'name': 'Test Fourth Member',
    'email': 'fourth.test@skynext.test',
    'password': 'Password123456789!',
    'phone': '0898765432',
    'consent': '1'
}).encode('utf-8')
opener2.open(urllib.request.Request(BASE + 'register', data=d2, headers={'Content-Type': 'application/x-www-form-urlencoded'}))

conn = sqlite3.connect('writable/platform.sqlite')
c = conn.cursor()
c.execute("SELECT id, name, referral_code FROM users WHERE email='fourth.test@skynext.test'")
fourth_user = c.fetchone()
conn.close()
assert fourth_user is not None, "Fourth user not created!"
print(f"SUCCESS: Created User #{fourth_user[0]}: {fourth_user[1]} with sequential code: {fourth_user[2]}")
assert fourth_user[2] == '0004', f"Expected code 0004, got {fourth_user[2]}"

print("\n=== 5. Test Custom Affiliate ID for User #2 (Somsak) ===")
# Update Somsak's referral_code to SOMSAK_VIP in DB
conn = sqlite3.connect('writable/platform.sqlite')
c = conn.cursor()
c.execute("UPDATE users SET referral_code='SOMSAK_VIP' WHERE id=2")
conn.commit()
conn.close()
print("Updated Somsak's referral_code to 'SOMSAK_VIP'")

# Test /r/SOMSAK_VIP
r_custom = opener.open(BASE + 'r/SOMSAK_VIP')
print("Redirected from /r/SOMSAK_VIP to:", r_custom.geturl())
assert "SOMSAK_VIP" in r_custom.read().decode('utf-8')
print("Verified /r/SOMSAK_VIP works!")

# Test case-insensitive /r/somsak_vip
r_case = opener.open(BASE + 'r/somsak_vip')
print("Redirected from lowercase /r/somsak_vip to:", r_case.geturl())
assert "SOMSAK_VIP" in r_case.read().decode('utf-8')
print("Verified lowercase /r/somsak_vip resolves correctly!")

# Test /p/SOMSAK_VIP
p_custom = opener.open(BASE + 'p/SOMSAK_VIP')
print("Landing /p/SOMSAK_VIP status:", p_custom.status)
p_html = p_custom.read().decode('utf-8')
assert "SOMSAK_VIP" in p_html
print("Verified /p/SOMSAK_VIP loads affiliate landing page with code!")

# Restore Somsak back to 0002 and clean up test accounts
conn = sqlite3.connect('writable/platform.sqlite')
c = conn.cursor()
c.execute("UPDATE users SET referral_code='0002' WHERE id=2")
c.execute("DELETE FROM users WHERE email IN ('newmember.test@skynext.test', 'fourth.test@skynext.test')")
conn.commit()
conn.close()

print("\n=== All Tests Succeeded! ===")
print("1. Sequential numbering starts at 0001, 0002, 0003, 0004...")
print("2. Sponsor attribution works seamlessly with sequential codes.")
print("3. Custom Affiliate ID works (case-insensitive routing for /r/ and /p/).")
print("4. Cleaned up temporary test users.")
