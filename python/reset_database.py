#!/usr/bin/env python3
import subprocess
import os
import sys
from pathlib import Path
from typing import Optional

def reset_database(
    db_name: str = "symfony",
    db_user: str = "root",
    db_pass: str = "root",
    db_host: str = "127.0.0.1",
    db_port: str = "3308",
    migrations_dir: Path = Path(__file__).parent.parent/ "migrations",
    confirm: Optional[bool] = True
) -> None:
    """
    Reset database: drop + create + clear migrations + create & apply migration.
    If confirm is None, ask user input.
    """
    # --------------------------
    # CONFIRMATION
    # --------------------------
    if confirm is None:
        print(f"⚠ WARNING: This will ERASE ALL TABLES in database '{db_name}'")
        user_input = input("Continue? (yes/no): ").strip().lower()
        if user_input != "yes":
            print("Cancelled.")
            return
    elif confirm is False:
        print("Cancelled.")
        return
    PROJECT_ROOT = Path(__file__).parent.parent.resolve()
    SCRIPT_PATH_IMPORT = PROJECT_ROOT / "bash" / "import-entity.sh"
    # --------------------------
    # CLEAN CACHE
    # --------------------------
    print("🏗 Clean cache before migration...")
    try:
         subprocess.run(["php", "bin/console", "cache:clear"], check=True, cwd=PROJECT_ROOT)
    except subprocess.CalledProcessError:
         print("❌ Failed to create migration")
         sys.exit(1)

    # --------------------------
    # DROP & CREATE DATABASE
    # --------------------------
    print("🗑 Dropping ALL tables...")
    mysql_command = (
        f"SET FOREIGN_KEY_CHECKS = 0; "
        f"DROP DATABASE IF EXISTS `{db_name}`; "
        f"CREATE DATABASE `{db_name}`;"
    )
    try:
        subprocess.run(
            [
                "mysql",
                f"-u{db_user}",
                f"-p{db_pass}",
                f"-h{db_host}",
                f"-P{db_port}",
                "-e",
                mysql_command
            ],
            check=True
        )
    except subprocess.CalledProcessError:
        print("❌ Failed to drop/create database")
        sys.exit(1)

    print("✔ Database recreated")

    # --------------------------
    # CLEAN MIGRATIONS
    # --------------------------
    print("🧹 Cleaning migrations...")
    if migrations_dir.exists():
        for file in migrations_dir.glob("*.php"):
            file.unlink()
    print("✔ Migrations directory cleared")


    # --------------------------
    # CREATE NEW MIGRATION
    # --------------------------
    print("🏗 Creating new migration...")

    try:
        subprocess.run(["php", "bin/console", "make:migration", "--no-interaction"], check=True, cwd=PROJECT_ROOT)
    except subprocess.CalledProcessError:
        print("❌ Failed to create migration")
        sys.exit(1)

    # --------------------------
    # APPLY MIGRATIONS
    # --------------------------
    print("🚀 Applying migration...")
    try:
        subprocess.run(["php", "bin/console", "doctrine:migrations:migrate", "--no-interaction"], check=True, cwd=PROJECT_ROOT)
    except subprocess.CalledProcessError:
        print("❌ Failed to apply migrations")
        sys.exit(1)

    print("🎉 Done: database reset + fresh migrations applied")

    try:
        subprocess.run(["bash", str(SCRIPT_PATH_IMPORT)], check=True, cwd=PROJECT_ROOT)
    except subprocess.CalledProcessError:
        print("❌ Failed to import migration")
        sys.exit(1)

# --------------------------
# CLI entry point
# --------------------------
def main():
    reset_database()


if __name__ == "__main__":
    main()
