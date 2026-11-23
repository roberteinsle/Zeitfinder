#!/usr/bin/env python3
"""Generate the GitHub wiki from handbuch/.

Usage: python3 scripts/build-wiki.py <path-to-wiki-checkout>

Copies every handbuch/*.md into the wiki checkout (Home.md stays Home.md),
removes wiki pages that no longer exist in handbuch/ and writes a _Sidebar.md.
Never edit the wiki by hand.
"""
import pathlib
import shutil
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
SOURCE = ROOT / "handbuch"
GENERATED_NOTE = "<!-- Generated from handbuch/ by scripts/build-wiki.py. Do not edit in the wiki. -->\n\n"


def main() -> int:
    if len(sys.argv) != 2:
        print(__doc__)
        return 1

    target = pathlib.Path(sys.argv[1]).resolve()
    if not (target / ".git").exists():
        print(f"{target} is not a git checkout of the wiki")
        return 1

    pages = sorted(SOURCE.glob("*.md"))
    names = {p.name for p in pages}

    for existing in target.glob("*.md"):
        if existing.name not in names and existing.name != "_Sidebar.md":
            existing.unlink()

    for page in pages:
        (target / page.name).write_text(GENERATED_NOTE + page.read_text(encoding="utf-8"), encoding="utf-8")

    sidebar = ["**Zeitfinder**", ""]
    for page in pages:
        title = page.stem.replace("-", " ")
        sidebar.append(f"- [{title}]({page.stem})")
    (target / "_Sidebar.md").write_text("\n".join(sidebar) + "\n", encoding="utf-8")

    print(f"Wrote {len(pages)} pages to {target}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
