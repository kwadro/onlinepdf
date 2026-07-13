from __future__ import annotations

from .context import GeneratorContext, normalize_fields
from .engine import render_template
from .writers import OUT, Writer

PER_ENTITY_TASKS = {"entity", "controller", "repository", "form_type", "import"}
ONCE_TASKS = {"dashboard", "export"}
ALL_TASKS = PER_ENTITY_TASKS | ONCE_TASKS


def _relation_needs_form(field: dict) -> bool:
    if field.get("type") != "relation":
        return False

    type_relation = field.get("typeRelation")
    role_relation = field.get("roleRelation")

    if type_relation == "ManyToOne" and role_relation == "master":
        return True
    if type_relation == "ManyToMany" and role_relation == "master":
        return True
    return type_relation == "OneToMany"


def generate_entity(writer: Writer, entity_name: str, entity: dict) -> None:
    fields = normalize_fields(entity["fields"])
    string_field = entity.get("stringfield", False)
    code = render_template(
        "entity.php.j2",
        name=entity_name,
        fields=fields,
        stringField=string_field,
    )
    writer.write_file(OUT / f"src/Entity/{entity_name}.php", code)


def generate_controller(writer: Writer, entity_name: str, entity: dict) -> None:
    if "disable_admin" in entity:
        return

    fields = normalize_fields(entity["fields"])
    code = render_template("controller.php.j2", name=entity_name, fields=fields)
    writer.write_file(
        OUT / f"src/Controller/Admin/{entity_name}CrudController.php",
        code,
    )


def generate_form_types(
    writer: Writer,
    ctx: GeneratorContext,
    entity: dict,
) -> None:
    if "disable_admin" in entity:
        return

    fields = normalize_fields(entity["fields"])
    for field in fields:
        if not _relation_needs_form(field):
            continue

        class_name = field.get("objectRelation")
        if not class_name or class_name == "self":
            continue

        if class_name in ctx.generated_forms:
            continue

        class_entity = ctx.all_entities.get(class_name)
        if class_entity is None:
            print(f"Warning: related entity '{class_name}' not found, skipping form type")
            continue

        class_fields = class_entity.get("fields")
        if not class_fields:
            continue

        code = render_template(
            "form-type.php.j2",
            name=class_name,
            fields=class_fields,
        )
        writer.write_file(OUT / f"src/Form/Type/{class_name}Type.php", code)
        ctx.generated_forms.add(class_name)


def generate_repository(writer: Writer, entity_name: str, entity: dict) -> None:
    related = entity.get("relatedSaleAndLocale", False)
    default_field = entity.get("default_field", False)
    url_key_field = entity.get("url_key_field", False)
    category_field = entity.get("category_field", False)
    author_field = entity.get("author_field", False)

    code = render_template(
        "repository.php.j2",
        name=entity_name,
        related=related,
        default=default_field,
        slug=url_key_field,
        category_field=category_field,
        author_field=author_field,
    )
    writer.write_file(OUT / f"src/Repository/{entity_name}Repository.php", code)


def generate_import(writer: Writer, entity_name: str, _entity: dict) -> None:
    code = render_template("import/import-handler.php.j2", name=entity_name).strip()
    writer.write_file(OUT / f"src/Import/Handler/{entity_name}ImportHandler.php", code)


def generate_dashboard_link(writer: Writer, groups: dict) -> None:
    dashboard_path = OUT / "src/Controller/Admin/DashboardController.php"

    code = render_template("dashboard-use.php.j2", groups=groups).strip()
    writer.insert_code_by_markers(
        file_path=dashboard_path,
        generated=code,
        start_marker="// @GENERATE USE START",
        end_marker="// @GENERATE USE FINISH",
    )

    code = render_template("dashboard-menu.php.j2", groups=groups).strip()
    writer.insert_code_by_markers(
        file_path=dashboard_path,
        generated=code,
        start_marker="// @GENERATE MENU START",
        end_marker="// @GENERATE MENU FINISH",
    )

    uk_translate_path = OUT / "translations/messages.uk.yaml"
    code = render_template(
        "translate.yaml.j2",
        groups=groups,
        lang_single="uk_single",
        lang="uk",
    ).strip()
    writer.insert_code_by_markers(
        file_path=uk_translate_path,
        generated=code,
        start_marker="#@GENERATE TRANSLATE START",
        end_marker="#@GENERATE TRANSLATE FINISH",
    )

    en_translate_path = OUT / "translations/messages.en.yaml"
    code = render_template(
        "translate.yaml.j2",
        groups=groups,
        lang_single="en_single",
        lang="en",
    ).strip()
    writer.insert_code_by_markers(
        file_path=en_translate_path,
        generated=code,
        start_marker="#@GENERATE TRANSLATE START",
        end_marker="#@GENERATE TRANSLATE FINISH",
    )


def generate_export_script(writer: Writer, groups: dict) -> None:
    code = render_template("export.sh.j2", groups=groups).strip()
    export_path = OUT / "bash/export-entity.sh"
    writer.write_file(export_path, code)

    if not writer.dry_run:
        with open(export_path, "a", encoding="utf-8") as handle:
            handle.write("\nphp bin/console app:export:csv User")

    code = render_template("import.sh.j2", groups=groups).strip()
    writer.write_file(OUT / "bash/import-entity.sh", code)


def run_tasks(
    tasks: set[str],
    ctx: GeneratorContext,
    writer: Writer,
    entity_filter: set[str] | None = None,
    group_filter: set[str] | None = None,
) -> None:
    per_entity = tasks & PER_ENTITY_TASKS
    once = tasks & ONCE_TASKS

    for entity_ref in ctx.iter_entities(entity_filter, group_filter):
        print(f"Group: {entity_ref.group_name}, Entity: {entity_ref.name}")

        if "entity" in per_entity:
            generate_entity(writer, entity_ref.name, entity_ref.entity)
        if "controller" in per_entity:
            generate_controller(writer, entity_ref.name, entity_ref.entity)
        if "form_type" in per_entity:
            generate_form_types(writer, ctx, entity_ref.entity)
        if "repository" in per_entity:
            generate_repository(writer, entity_ref.name, entity_ref.entity)
        if "import" in per_entity:
            generate_import(writer, entity_ref.name, entity_ref.entity)

    if "dashboard" in once:
        generate_dashboard_link(writer, ctx.groups)
    if "export" in once:
        generate_export_script(writer, ctx.groups)
