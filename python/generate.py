#!/usr/bin/env python3
from __future__ import annotations

import argparse
import sys
from pathlib import Path

from generator.config import DEFAULT_CONFIG, load_config, validate_config
from generator.context import GeneratorContext
from generator.tasks import ALL_TASKS, run_tasks
from generator.writers import Writer

try:
    from reset_database import reset_database
except ImportError:
    reset_database = None


def parse_args(argv: list[str] | None = None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Generate Symfony entities, CRUD, forms and helpers from recipe YAML.",
    )
    parser.add_argument(
        "--config",
        type=Path,
        default=DEFAULT_CONFIG,
        help="Path to structure-recipe.yaml (default: python/structure-recipe.yaml)",
    )
    parser.add_argument(
        "--only",
        nargs="+",
        choices=sorted(ALL_TASKS),
        metavar="TASK",
        help=(
            "Run only selected tasks: "
            + ", ".join(sorted(ALL_TASKS))
        ),
    )
    parser.add_argument(
        "--entity",
        nargs="+",
        metavar="NAME",
        help="Generate only selected entities (e.g. Recipe RecipeCategory)",
    )
    parser.add_argument(
        "--group",
        nargs="+",
        metavar="NAME",
        help="Generate only entities from selected groups (catalog, setting, ...)",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Show what would be generated without writing files",
    )
    parser.add_argument(
        "--reset-db",
        action="store_true",
        help="Drop database, recreate migrations and import data after generation",
    )
    parser.add_argument(
        "--skip-warnings",
        action="store_true",
        help="Do not print YAML validation warnings",
    )
    return parser.parse_args(argv)


def main(argv: list[str] | None = None) -> int:
    args = parse_args(argv)
    tasks = set(args.only) if args.only else ALL_TASKS
    entity_filter = set(args.entity) if args.entity else None
    group_filter = set(args.group) if args.group else None

    cfg = load_config(args.config)
    if not args.skip_warnings:
        for warning in validate_config(cfg):
            print(f"Warning: {warning}")

    ctx = GeneratorContext.from_config(cfg)
    writer = Writer(dry_run=args.dry_run)

    run_tasks(
        tasks=tasks,
        ctx=ctx,
        writer=writer,
        entity_filter=entity_filter,
        group_filter=group_filter,
    )

    if args.reset_db:
        if args.dry_run:
            print("[dry-run] would reset database")
        elif reset_database is None:
            print("Error: reset_database module not available", file=sys.stderr)
            return 1
        else:
            reset_database()

    print("✔ Генерація завершена")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
