import sqlite3

# bcrypt hash for Admin123456789!
# We can use php or precomputed bcrypt hash:
# $2y$10$eE07F8m/k2eZJmNkWUOB4eA7P34yB3sVj8w7r4WqfT54J3QnC/8mS is not known, but let's compute or run php script
db_path = r'c:\xampp\htdocs\skynextinter.com\writable\platform.sqlite'
conn = sqlite3.connect(db_path)
c = conn.cursor()
c.execute("SELECT id, email, role FROM users WHERE role='admin'")
print("Admins:", c.fetchall())
conn.close()
