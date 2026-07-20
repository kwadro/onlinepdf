from __future__ import annotations

import argparse
import json
import subprocess
import sys
from pathlib import Path

import yaml

from recipe_catalog.ai.generator import RecipeGenerator
from recipe_catalog.builder import PROJECT_ROOT, RecipeCatalogBuilder, slugify


def _run_symfony(args: list[str]) -> int:
    command = ["php", "bin/console", *args]
    completed = subprocess.run(command, cwd=PROJECT_ROOT, check=False)
    return completed.returncode


def _load_simple_yaml(path: Path) -> dict:
    with path.open(encoding="utf-8") as handle:
        data = yaml.safe_load(handle)

    if not isinstance(data, dict):
        raise ValueError(f"Expected YAML object in {path}")

    return data


def _validate_payload(payload: dict) -> list[str]:
    errors: list[str] = []

    if payload.get("schema_version") != 1:
        errors.append("schema_version must be 1")

    if not payload.get("site"):
        errors.append("site is required")

    recipes = payload.get("recipes")
    if not isinstance(recipes, list) or not recipes:
        errors.append("recipes must be a non-empty array")
        return errors

    for index, recipe in enumerate(recipes, start=1):
        if not isinstance(recipe, dict):
            errors.append(f"recipes[{index - 1}] must be an object")
            continue

        translations = recipe.get("translations")
        if not isinstance(translations, list) or not translations:
            errors.append(f"recipes[{index - 1}] must contain translations")
            continue

        for translation in translations:
            if not translation.get("name") or not translation.get("slug"):
                errors.append(f"recipes[{index - 1}] translation requires name and slug")

    return errors


def cmd_new(args: argparse.Namespace) -> int:
    builder = RecipeCatalogBuilder.from_template(site=args.site)

    if args.name:
        recipe = {
            "external_key": args.slug or None,
            "position": args.position,
            "prep_time_min": args.prep_time,
            "cook_time_min": args.cook_time,
            "servings": args.servings,
            "image": args.image,
            "categories": args.category or [],
            "translation": {
                "locale": args.locale,
                "name": args.name,
                "slug": args.slug,
                "short_description": args.short_description,
                "description": args.description,
                "author_email": args.author_email,
            },
        }
        builder.recipes = []
        builder.add_recipe(recipe)

    output = Path(args.output)
    builder.save(output, include_reference_data=not args.without_reference_data)
    print(f"Created {output}")
    return 0


def cmd_build(args: argparse.Namespace) -> int:
    data = _load_simple_yaml(Path(args.input))
    builder = RecipeCatalogBuilder.from_simple_yaml(data)
    output = Path(args.output)
    builder.save(output, include_reference_data=not args.without_reference_data)
    print(f"Built {output}")
    return 0


def cmd_validate(args: argparse.Namespace) -> int:
    payload = json.loads(Path(args.file).read_text(encoding="utf-8"))
    errors = _validate_payload(payload)

    if errors:
        for error in errors:
            print(f"ERROR: {error}", file=sys.stderr)
        return 1

    print(f"OK: {args.file}")
    return 0


def cmd_export(args: argparse.Namespace) -> int:
    command = ["app:recipe:export-json", args.output]
    if args.site:
        command.extend(["--site", args.site])
    for recipe_id in args.recipe_id or []:
        command.extend(["--recipe-id", str(recipe_id)])
    if args.without_reference_data:
        command.append("--without-reference-data")
    return _run_symfony(command)


def cmd_generate(args: argparse.Namespace) -> int:
    generator = RecipeGenerator.from_env()
    data = generator.generate(
        args.title,
        site=args.site,
        locale=args.locale,
        category=args.category,
        categories=args.category_list,
        cuisine=args.cuisine,
        author_email=args.author_email,
        servings=args.servings,
        notes=args.notes,
        publish_ready=not args.draft,
    )

    slug = slugify(data["recipe"]["translation"]["slug"])
    yaml_path = Path(args.output_yaml or f"export/generated/{slug}.yaml")
    json_path = Path(args.output_json or f"export/generated/{slug}.json")

    generator.save_yaml(data, yaml_path)
    builder = RecipeCatalogBuilder.from_simple_yaml(data)
    builder.save(json_path, include_reference_data=not args.without_reference_data)

    print(f"Generated YAML: {yaml_path}")
    print(f"Generated JSON: {json_path}")

    if args.publish:
        if args.validate:
            errors = _validate_payload(builder.to_dict())
            if errors:
                for error in errors:
                    print(f"ERROR: {error}", file=sys.stderr)
                return 1
        return _run_symfony(["app:recipe:import-json", str(json_path)])

    return 0


def cmd_publish(args: argparse.Namespace) -> int:
    input_path = Path(args.input)
    json_path = Path(args.output_json) if args.output_json else input_path.with_suffix(".json")

    if input_path.suffix.lower() in {".yaml", ".yml"}:
        data = _load_simple_yaml(input_path)
        builder = RecipeCatalogBuilder.from_simple_yaml(data)
        builder.save(json_path, include_reference_data=not args.without_reference_data)
        print(f"Built {json_path}")
    else:
        json_path = input_path

    if args.validate:
        payload = json.loads(json_path.read_text(encoding="utf-8"))
        errors = _validate_payload(payload)
        if errors:
            for error in errors:
                print(f"ERROR: {error}", file=sys.stderr)
            return 1

    return _run_symfony(["app:recipe:import-json", str(json_path)])


def cmd_import(args: argparse.Namespace) -> int:
    file_path = Path(args.file)
    if args.validate:
        payload = json.loads(file_path.read_text(encoding="utf-8"))
        errors = _validate_payload(payload)
        if errors:
            for error in errors:
                print(f"ERROR: {error}", file=sys.stderr)
            return 1

    return _run_symfony(["app:recipe:import-json", str(file_path)])


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Build, validate, export and import recipe catalog JSON.",
    )
    subparsers = parser.add_subparsers(dest="command", required=True)

    parser_new = subparsers.add_parser("new", help="Create JSON from template")
    parser_new.add_argument("--output", default="export/new-recipe.json")
    parser_new.add_argument("--site", default="symfony.local")
    parser_new.add_argument("--name", help="Recipe title")
    parser_new.add_argument("--slug", help="Recipe slug")
    parser_new.add_argument("--locale", default="uk")
    parser_new.add_argument("--position", type=int, default=1)
    parser_new.add_argument("--prep-time", type=int, default=15, dest="prep_time")
    parser_new.add_argument("--cook-time", type=int, default=30, dest="cook_time")
    parser_new.add_argument("--servings", type=int, default=4)
    parser_new.add_argument("--image")
    parser_new.add_argument("--category", action="append")
    parser_new.add_argument("--short-description")
    parser_new.add_argument("--description")
    parser_new.add_argument("--author-email", default="kwadro2010@gmail.com")
    parser_new.add_argument("--without-reference-data", action="store_true")
    parser_new.set_defaults(handler=cmd_new)

    parser_build = subparsers.add_parser("build", help="Build JSON from simple YAML")
    parser_build.add_argument("input", help="Simple recipe YAML file")
    parser_build.add_argument("--output", default="export/new-recipe.json")
    parser_build.add_argument("--without-reference-data", action="store_true")
    parser_build.set_defaults(handler=cmd_build)

    parser_validate = subparsers.add_parser("validate", help="Validate catalog JSON")
    parser_validate.add_argument("file")
    parser_validate.set_defaults(handler=cmd_validate)

    parser_export = subparsers.add_parser("export", help="Export recipes from DB to JSON")
    parser_export.add_argument("--output", default="export/recipe-catalog.json")
    parser_export.add_argument("--site")
    parser_export.add_argument("--recipe-id", action="append", type=int)
    parser_export.add_argument("--without-reference-data", action="store_true")
    parser_export.set_defaults(handler=cmd_export)

    parser_import = subparsers.add_parser("import", help="Import catalog JSON into DB")
    parser_import.add_argument("file")
    parser_import.add_argument("--validate", action="store_true")
    parser_import.set_defaults(handler=cmd_import)

    parser_generate = subparsers.add_parser("generate", help="Generate recipe with LLM and save YAML/JSON")
    parser_generate.add_argument("title", help="Recipe title, e.g. 'Український борщ'")
    parser_generate.add_argument("--site", default="symfony.local")
    parser_generate.add_argument("--locale", default="uk")
    parser_generate.add_argument("--category", default="Перші страви")
    parser_generate.add_argument("--category-list", action="append", dest="category_list")
    parser_generate.add_argument("--cuisine", default="Українська")
    parser_generate.add_argument("--author-email", default="kwadro2010@gmail.com")
    parser_generate.add_argument("--servings", type=int)
    parser_generate.add_argument("--notes", help="Extra generation instructions")
    parser_generate.add_argument("--output-yaml", help="Path for generated YAML")
    parser_generate.add_argument("--output-json", help="Path for generated JSON")
    parser_generate.add_argument("--without-reference-data", action="store_true")
    parser_generate.add_argument("--draft", action="store_true", help="Generate without publish defaults")
    parser_generate.add_argument("--validate", action="store_true")
    parser_generate.add_argument("--publish", action="store_true", help="Import generated JSON into DB")
    parser_generate.set_defaults(handler=cmd_generate)

    parser_publish = subparsers.add_parser("publish", help="Build YAML to JSON and import to site")
    parser_publish.add_argument("input", help="Recipe YAML or JSON file")
    parser_publish.add_argument("--output-json", help="JSON output when input is YAML")
    parser_publish.add_argument("--without-reference-data", action="store_true")
    parser_publish.add_argument("--validate", action="store_true")
    parser_publish.set_defaults(handler=cmd_publish)

    return parser


def main(argv: list[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)
    return args.handler(args)
