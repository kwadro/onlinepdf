import yaml
import inflect
from reset_database import reset_database
from jinja2 import Template
from pathlib import Path
p = inflect.engine()
# singularize для collection
def singularize(word):
    result = p.singular_noun(word)
    if result:
        return result
    return word

# capitalize першу букву
def capitalize(word):
    return word[:1].upper() + word[1:] if word else ''

BASE = Path(__file__).resolve().parent
OUT = BASE.parent

# add service function for getter and setter in template
def to_pascal_case(value):
    return ''.join(word.capitalize() for word in value.split('_'))

TYPE_MAP = {
    "integer": "int",
    "string": "string",
    "datetime": "\\DateTimeInterface",
    "date": "\\DateTimeInterface",
    "float": "float",
    "boolean": "bool",
    "text": "string",
    "choice": "string",
    "image": "string",
    "relation": "relation",
    "select": "string",
    "money": "string"
}
DOCTRINE_TYPE_MAP = {
    "integer": "integer",
    "string": "string",
    "datetime": "datetime",
    "date": "datetime",
    "float": "float",
    "boolean": "bool",
    "text": "text",
    "choice": "string",
    "image": "string",
    "relation": "relation",
    "select": "string",
    "money": "string"
}

def normalize_fields(fields):
    normalized = []
    for f in fields:
        t = f.get("type")
        f["php_type"] = TYPE_MAP.get(t, t)
        f["doc_type"] = DOCTRINE_TYPE_MAP.get(t, t)
        normalized.append(f)
    return normalized

def load_yaml():
    with open(BASE / "structure-recipe.yaml", "r", encoding="utf-8") as f:
        return yaml.safe_load(f)

def render_template(template_file, **ctx):
    tpl = Template(open(BASE / "templates" / template_file).read())
    tpl.environment.filters['pascal'] = to_pascal_case
    tpl.environment.filters['singularize'] = singularize
    tpl.environment.filters['capitalize'] = capitalize
    return tpl.render(**ctx)

def write_file(path: Path, content: str):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8")

def generate_entity(entity_name,entity):
    fields = normalize_fields(entity["fields"])
    stringField = False
    if ('stringfield' in entity):
        stringField = entity['stringfield']
    code = render_template("entity.php.j2", name=entity_name, fields=fields, stringField=stringField)
    write_file(OUT / f"src/Entity/{entity_name}.php", code)

def generate_controller(entity_name,entity):
    fields = normalize_fields(entity["fields"])
    code = render_template("controller.php.j2", name=entity_name, fields=fields)
    write_file(
        OUT / f"src/Controller/Admin/{entity_name}CrudController.php", code
    )

def generate_form_type(entities, entity, entity_name,additionalEntities):

    fields = normalize_fields(entity["fields"])
    for field in fields:
        if (field.get('type')=='relation') :
            if ((field.get('typeRelation')=='ManyToOne'  and field.get('roleRelation')=='master') or (field.get('typeRelation')=='ManyToMany'  and field.get('roleRelation')=='master') or field.get('typeRelation')=='OneToMany') :
                className=field.get('objectRelation')
                print(f"className : " + className)
                if(className!='self'):
                    if className in entities:
                       classEntity = entities.get(className)
                    else:
                       classEntity = additionalEntities.get(className)

                    classFields = classEntity.get("fields")
                    code = render_template("form-type.php.j2", name=className, fields=classFields)
                    write_file(OUT / f"src/Form/Type/{field.get('objectRelation')}Type.php", code)

def generate_repository(entity_name, entity):
    related = False
    if ('relatedSaleAndLocale' in entity):
        related = entity['relatedSaleAndLocale']

    default_field = False
    if ('default_field' in entity):
            default_field = entity['default_field']

    url_key_field = False
    if ('url_key_field' in entity):
                url_key_field = entity['url_key_field']

    category_field = False
    if ('category_field' in entity):
                category_field = entity['category_field']

    author_field = False
    if ('category_field' in entity):
                author_field = entity['author_field']

    code = render_template("repository.php.j2",
        name=entity_name,
        related=related,
        default=default_field,
        slug=url_key_field,
        category_field=category_field,
        author_field=author_field
       )
    write_file(OUT / f"src/Repository/{entity_name}Repository.php", code)

def insert_code_by_markers(file_path, generated, start_marker, end_marker):
    with open(file_path, "r", encoding="utf-8") as f:
        original = f.read()
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
    with open(file_path, "w", encoding="utf-8") as f:
        f.write(new_content)
    print(f"Updated: {file_path}")

def generate_dashboard_link(groups):
    code = render_template("dashboard-use.php.j2", groups=groups).strip()
    dashboardPath = OUT / "src/Controller/Admin/DashboardController.php"
    insert_code_by_markers(
       file_path = dashboardPath,
       generated = code,
       start_marker = '// @GENERATE USE START',
       end_marker = '// @GENERATE USE FINISH',
    )
    code = render_template("dashboard-menu.php.j2", groups=groups).strip()
    insert_code_by_markers(
           file_path = dashboardPath,
           generated = code,
           start_marker = '// @GENERATE MENU START',
           end_marker = '// @GENERATE MENU FINISH',
        )

    ukTranslatePath = OUT / "translations/messages.uk.yaml"
    code = render_template("translate.yaml.j2", groups=groups, lang_single='uk_single', lang='uk').strip()
    insert_code_by_markers(
           file_path = ukTranslatePath,
           generated = code,
           start_marker = '#@GENERATE TRANSLATE START',
           end_marker = '#@GENERATE TRANSLATE FINISH',
        )

    enTranslatePath = OUT / "translations/messages.en.yaml"
    code = render_template("translate.yaml.j2", groups=groups, lang_single='en_single', lang='en').strip()
    insert_code_by_markers(
           file_path = enTranslatePath,
           generated = code,
           start_marker = '#@GENERATE TRANSLATE START',
           end_marker = '#@GENERATE TRANSLATE FINISH',
    )
#     yaml_path = OUT / "config/packages/easy_admin.yaml"
#     write_file(yaml_path, yaml.dump(data, allow_unicode=True))

def generate_import(entity_name, entity):
    code = render_template("import/import-handler.php.j2", name=entity_name).strip()
    write_file(OUT / f"src/Import/Handler/{entity_name}ImportHandler.php", code)

def generate_export_script(groups):
    code = render_template("export.sh.j2", groups=groups).strip()
    write_file(OUT / f"bash/export-entity.sh", code)
    code = render_template("import.sh.j2", groups=groups).strip()
    write_file(OUT / f"bash/import-entity.sh", code)

def main():
    cfg = load_yaml()
    groups = cfg.get("groups", {})
    for group_name, group in groups.items():
        if(group_name == 'catalog'):
            catalogEntities = group.get("entities", {})

        if(group_name == 'setting'):
            settingEntities = group.get("entities", {})

    for group_name, group in groups.items():
        print("Group : " + group_name)
        entities = group.get("entities", {})
        for entity_name, entity in entities.items():
            print("Entity Name : " + entity_name)
            generate_entity(entity_name, entity)
            generate_controller(entity_name, entity)
            if(group_name == 'catalog'):
                generate_form_type(entities, entity, entity_name, settingEntities)
            else:
                generate_form_type(entities, entity, entity_name, catalogEntities)

            generate_repository(entity_name, entity)
            generate_dashboard_link(groups)
            generate_export_script(groups)
            generate_import(entity_name, entity)

    reset_database()
#   generate_easy_admin_yaml(entities)
    print("✔ Генерація завершена")

if __name__ == "__main__":
    main()
