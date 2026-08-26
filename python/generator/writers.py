from __future__ import annotations

from pathlib import Path

OUT = Path(__file__).resolve().parent.parent.parent


class Writer:
    def __init__(self, dry_run: bool = False):
        self.dry_run = dry_run
        self.written: list[Path] = []

    def write_file(self, path: Path, content: str) -> None:
        if self.dry_run:
            print(f"[dry-run] would write: {path}")
            return

        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content, encoding="utf-8")
        self.written.append(path)
#         print(f"Written: {path}")

    def insert_code_by_markers(
        self,
        file_path: Path,
        generated: str,
        start_marker: str,
        end_marker: str,
    ) -> None:
        if self.dry_run:
            print(f"[dry-run] would update markers in: {file_path}")
            return

        original = file_path.read_text(encoding="utf-8")
        if start_marker not in original or end_marker not in original:
            raise ValueError(f"Markers not found in {file_path}")

        before = original.split(start_marker)[0]
        after = original.split(end_marker)[1]
        new_content = (
            before
            + start_marker
            + "\n"
            + generated
            + "\n"
            + end_marker
            + after
        )
        file_path.write_text(new_content, encoding="utf-8")
        self.written.append(file_path)
        print(f"Updated: {file_path}")
