##
#  Ma-Moulinette
#  --------------
#  Copyright (c) 2021-2025.
#  Laurent HADJADJ <laurent_h@me.com>.
#  Licensed Creative Common  CC-BY-NC-SA 4.0.
#  ---
#  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
#  http://creativecommons.org/licenses/by-nc-sa/4.0/
##

import os, re, zipfile, shutil

SRC = "tables.sql"
OUTDIR = "sql"
ZIPNAME = "ma_moulinette_sql.zip"

# Dossiers cibles
DIRS = [
    "00_init", "10_schema", "20_tables", "30_constraints",
    "40_indexes", "50_functions", "60_comments"
]

# Helpers d’écriture
def write(path, content, mode="w", encoding="utf-8"):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, mode, encoding=encoding, newline="\n") as f:
        f.write(content.strip() + "\n")

# Prépare la sortie
if os.path.exists(OUTDIR):
    shutil.rmtree(OUTDIR)
for d in DIRS: os.makedirs(os.path.join(OUTDIR, d), exist_ok=True)

with open(SRC, "r", encoding="utf-8") as f:
    sql = f.read()

# Normalise fins de ligne
sql = sql.replace("\r\n", "\n")

# 1) Extraction des parties DB / rôle / ext / search_path (si présentes)
init_parts = {
    "00_drop_database.sql": r"(?is)\bDROP\s+DATABASE\b.*?;",
    "01_create_roles.sql":  r"(?is)\bCREATE\s+ROLE\b.*?;",
    "02_create_database.sql": r"(?is)\bCREATE\s+DATABASE\b.*?;",
    "03_extensions.sql":    r"(?is)\bCREATE\s+EXTENSION\b.*?;",
}
for fname, pattern in init_parts.items():
    m = re.findall(pattern, sql)
    if m:
        write(os.path.join(OUTDIR, "00_init", fname), "\n\n".join(m))

# search_path (ALTER DATABASE/ROLE … SET search_path)
sp = re.findall(r"(?is)\bALTER\s+(?:DATABASE|ROLE)\b.*?SET\s+search_path\b.[^;]*", sql)
if sp:
    write(os.path.join(OUTDIR, "00_init", "04_search_path.sql"), "\n\n".join(sp))

# 2) SCHEMA
schema_def = re.findall(r"(?is)\bCREATE\s+SCHEMA\b.[^;]*", sql)
if schema_def:
    write(os.path.join(OUTDIR, "10_schema", "01_create_schema.sql"), "\n\n".join(schema_def))

# 3) Tables : capture DROP TABLE … ; puis CREATE TABLE … ;
#    On garde l’ordre d’apparition et le corps exact (aucune modif).
table_blocks = []
tbl_regex = r"(?is)(DROP\s+TABLE\s+IF\s+EXISTS\s+[\w\.]+\s*;)?\s*(CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?([\w\.]+)\s*\(.*?\);)"

for m in re.finditer(tbl_regex, sql):
    drop_stmt = (m.group(1) or "").strip()
    create_stmt = m.group(2).strip()
    full_name = m.group(4)
    table_blocks.append((full_name, drop_stmt, create_stmt))

# Écriture sans doublon
written = set()
for full_name, drop_stmt, create_stmt in table_blocks:
    table_name = full_name.split(".")[-1]

    # Évite les réécritures multiples
    if table_name in written:
        continue
    written.add(table_name)

    content = []

    # Ajouter DROP seulement s’il existait en amont
    if drop_stmt:
        content.append(drop_stmt)

    # Ajouter la création
    content.append(create_stmt)

    final_sql = "\n\n".join(content)

    write(os.path.join(OUTDIR, "20_tables", f"{table_name}.sql"), final_sql)
# 4) Contraintes explicites (ALTER TABLE … ADD CONSTRAINT …)
cons = re.findall(r"(?is)\bALTER\s+TABLE\b.*?ADD\s+CONSTRAINT\b.[^;]*", sql)
if cons:
    write(os.path.join(OUTDIR, "30_constraints", "constraints.sql"), "\n\n".join(cons))

# 5) Index (CREATE INDEX …)
indexes = re.findall(r"(?is)\bCREATE\s+INDEX\b.[^;]*", sql)
if indexes:
    write(os.path.join(OUTDIR, "40_indexes", "indexes.sql"), "\n\n".join(indexes))

# 6) Functions/DO blocks (CREATE FUNCTION … ; / DO $$ … $$ ;)
funcs = re.findall(r"(?is)\bCREATE\s+FUNCTION\b.*?;\s*", sql)
do_blocks = re.findall(r"(?is)\bDO\s+\$\$.*?\$\$\s*;", sql)
if funcs:
    write(os.path.join(OUTDIR, "50_functions", "functions.sql"), "\n\n".join(funcs))
if do_blocks:
    write(os.path.join(OUTDIR, "50_functions", "do_blocks.sql"), "\n\n".join(do_blocks))

# 7) Comments (COMMENT ON …)
comments = re.findall(r"(?is)\bCOMMENT\s+ON\b.[^;]*", sql)
if comments:
    write(os.path.join(OUTDIR, "60_comments", "comments.sql"), "\n\n".join(comments))

# 8) Master installer
master = []
master += [r"\i sql/00_init/00_drop_database.sql"] if os.path.exists(os.path.join(OUTDIR, "00_init", "00_drop_database.sql")) else []
master += [r"\i sql/00_init/01_create_roles.sql"] if os.path.exists(os.path.join(OUTDIR, "00_init", "01_create_roles.sql")) else []
master += [r"\i sql/00_init/02_create_database.sql"] if os.path.exists(os.path.join(OUTDIR, "00_init", "02_create_database.sql")) else []
master += [r"\i sql/00_init/03_extensions.sql"] if os.path.exists(os.path.join(OUTDIR, "00_init", "03_extensions.sql")) else []
master += [r"\i sql/00_init/04_search_path.sql"] if os.path.exists(os.path.join(OUTDIR, "00_init", "04_search_path.sql")) else []

if os.path.exists(os.path.join(OUTDIR, "10_schema", "01_create_schema.sql")):
    master += [r"\i sql/10_schema/01_create_schema.sql"]

for _, _, block in table_blocks:
    name = re.search(r"(?is)CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([\w\.]+)", block)
    if name:
        simple = name.group(1).split(".")[-1]
        if os.path.exists(os.path.join(OUTDIR, "20_tables", f"{simple}.sql")):
            master += [fr"\i sql/20_tables/{simple}.sql"]

if os.path.exists(os.path.join(OUTDIR, "30_constraints", "constraints.sql")):
    master += [r"\i sql/30_constraints/constraints.sql"]
if os.path.exists(os.path.join(OUTDIR, "40_indexes", "indexes.sql")):
    master += [r"\i sql/40_indexes/indexes.sql"]
if os.path.exists(os.path.join(OUTDIR, "50_functions", "functions.sql")):
    master += [r"\i sql/50_functions/functions.sql"]
if os.path.exists(os.path.join(OUTDIR, "50_functions", "do_blocks.sql")):
    master += [r"\i sql/50_functions/do_blocks.sql"]
if os.path.exists(os.path.join(OUTDIR, "60_comments", "comments.sql")):
    master += [r"\i sql/60_comments/comments.sql"]

write(os.path.join(OUTDIR, "99_master_install.sql"), "\n".join(master))

# 9) Zip
with zipfile.ZipFile(ZIPNAME, "w", zipfile.ZIP_DEFLATED) as z:
    for root, _, files in os.walk(OUTDIR):
        for f in files:
            ap = os.path.join(root, f)
            z.write(ap, os.path.relpath(ap, OUTDIR))

print(f"OK -> arborescence '{OUTDIR}/' et archive '{ZIPNAME}' générées.")
