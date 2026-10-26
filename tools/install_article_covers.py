"""Install generated article covers, preserving covers already set by editors."""
import hashlib
import json
from pathlib import Path
import shutil
import sqlite3
import sys

ROOT = Path(__file__).resolve().parents[1]
manifest = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
seed_path = ROOT / "app/Database/blog_seed.json"
seeds = json.loads(seed_path.read_text(encoding="utf-8"))
db = sqlite3.connect(ROOT / "writable/platform.sqlite")
db.row_factory = sqlite3.Row
updates = []
for item in manifest:
    slug = item["slug"]
    source = Path(item["source"])
    if not source.is_file() or source.read_bytes()[:8] != b"\x89PNG\r\n\x1a\n":
        raise ValueError(f"Missing or invalid PNG: {slug}")
    filename = hashlib.md5(("article-cover:" + slug).encode()).hexdigest() + ".png"
    relative = "platform/uploads/" + filename
    destination = ROOT / "public" / relative
    shutil.copy2(source, destination)
    row = db.execute("SELECT id,cover_image,updated_at FROM blog WHERE slug=?", (slug,)).fetchone()
    if row and not row["cover_image"]:
        updates.append(dict(row))
    for seed in seeds:
        if seed["slug"] == slug and not seed.get("cover_image"):
            seed["cover_image"] = relative
    item["cover_image"] = relative

backup = ROOT / "writable/article-covers-before.json"
if updates and not backup.exists():
    backup.write_text(json.dumps(updates, ensure_ascii=False, indent=2), encoding="utf-8")
with db:
    for item in manifest:
        db.execute("UPDATE blog SET cover_image=?,updated_at=datetime('now') WHERE slug=? AND cover_image=''",
                   (item["cover_image"], item["slug"]))
seed_path.write_text(json.dumps(seeds, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
Path(sys.argv[1]).write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
print(f"Installed {len(manifest)} image files; updated {len(updates)} articles.")
