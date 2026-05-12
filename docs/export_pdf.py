#!/usr/bin/env python3
"""Convert collaboration markdown docs to PDF using weasyprint."""

import markdown
from weasyprint import HTML, CSS
import os

CSS_STYLES = """
@page {
    margin: 2cm 2.2cm;
    @bottom-center {
        content: counter(page) " / " counter(pages);
        font-size: 9pt;
        color: #999;
    }
}
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 11pt;
    line-height: 1.6;
    color: #1a1a1a;
}
h1 {
    font-size: 18pt;
    color: #0d3d2b;
    border-bottom: 3px solid #16a34a;
    padding-bottom: 8px;
    margin-top: 0;
}
h2 {
    font-size: 13pt;
    color: #0d3d2b;
    border-left: 4px solid #16a34a;
    padding-left: 10px;
    margin-top: 28px;
}
h3 {
    font-size: 11pt;
    color: #1a1a1a;
    margin-top: 18px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin: 14px 0;
    font-size: 10pt;
}
th {
    background: #0d3d2b;
    color: #fff;
    padding: 8px 12px;
    text-align: left;
    font-weight: 600;
}
td {
    padding: 7px 12px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}
tr:nth-child(even) td {
    background: #f9fafb;
}
blockquote {
    background: #f0fdf4;
    border-left: 4px solid #16a34a;
    margin: 12px 0;
    padding: 10px 16px;
    font-size: 10pt;
    color: #374151;
}
code, pre {
    background: #f3f4f6;
    font-family: 'DejaVu Sans Mono', monospace;
    font-size: 9.5pt;
    border-radius: 4px;
}
pre {
    padding: 14px 16px;
    line-height: 1.7;
    border: 1px solid #e5e7eb;
    white-space: pre-wrap;
}
code {
    padding: 1px 5px;
}
p { margin: 8px 0; }
li { margin: 4px 0; }
hr {
    border: none;
    border-top: 1px solid #d1d5db;
    margin: 20px 0;
}
strong { color: #111; }
.page-break { page-break-after: always; }
"""

docs = [
    ("TERM_SHEET_SLV_Adcellent.md",         "TERM_SHEET_SLV_Adcellent.pdf"),
    ("COLLABORATION_AGREEMENT_SLV_Adcellent.md", "COLLABORATION_AGREEMENT_SLV_Adcellent.pdf"),
]

docs_dir = os.path.dirname(os.path.abspath(__file__))

for md_file, pdf_file in docs:
    md_path  = os.path.join(docs_dir, md_file)
    pdf_path = os.path.join(docs_dir, pdf_file)

    with open(md_path, "r", encoding="utf-8") as f:
        md_content = f.read()

    html_body = markdown.markdown(
        md_content,
        extensions=["tables", "fenced_code", "nl2br"]
    )

    full_html = f"""<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{md_file}</title></head>
<body>{html_body}</body>
</html>"""

    HTML(string=full_html, base_url=docs_dir).write_pdf(
        pdf_path,
        stylesheets=[CSS(string=CSS_STYLES)]
    )
    print(f"✓  {pdf_file}")

print("Done.")
