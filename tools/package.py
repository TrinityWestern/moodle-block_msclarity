#!/usr/bin/env python3
"""Build a single Moodle-installable ZIP, excluding development-only files."""

from pathlib import Path
import re
import zipfile

root = Path(__file__).resolve().parent.parent
version = (root / "version.php").read_text()
release = re.search(r"\$plugin->release\s*=\s*'([^']+)'", version).group(1)
output = root / "dist" / f"block_msclarity-{release}.zip"
output.parent.mkdir(exist_ok=True)
files = [root / name for name in (
    "version.php", "block_msclarity.php", "settings.php", "lib.php", "refresh.php", "README.md", "COPYING.txt",
)]
for directory in ("classes", "db", "js", "lang", "templates"):
    files.extend(path for path in (root / directory).rglob("*") if path.is_file())

with zipfile.ZipFile(output, "w", zipfile.ZIP_DEFLATED) as archive:
    for source in sorted(files):
        archive.write(source, "msclarity/" + source.relative_to(root).as_posix())

with zipfile.ZipFile(output) as archive:
    if archive.testzip() is not None or "msclarity/version.php" not in archive.namelist():
        raise RuntimeError("Invalid plugin package")
print(output)
