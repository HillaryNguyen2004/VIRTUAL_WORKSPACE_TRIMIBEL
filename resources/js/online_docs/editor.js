import { Editor, Extension } from "@tiptap/core";
import { StarterKit } from "@tiptap/starter-kit";
import { Underline } from "@tiptap/extension-underline";
import { TextAlign } from "@tiptap/extension-text-align";
import { Highlight } from "@tiptap/extension-highlight";
import { TextStyle } from "@tiptap/extension-text-style";
import { Color } from "@tiptap/extension-color";
import { Image } from "@tiptap/extension-image";
import { Table } from "@tiptap/extension-table";
import { TableRow } from "@tiptap/extension-table-row";
import { TableHeader } from "@tiptap/extension-table-header";
import { TableCell } from "@tiptap/extension-table-cell";
import DOMPurify from "dompurify";
import axios from "axios";

const editorRoot = document.getElementById("doc-editor");
const form = document.getElementById("doc-editor-form");
const contentField = document.getElementById("doc-content");
const statusEl = document.getElementById("doc-save-status");
const toolbar = document.getElementById("doc-toolbar");
const headingSelect = document.getElementById("doc-heading");
const fontSizeSelect = document.getElementById("doc-font-size");
const fontFamilySelect = document.getElementById("doc-font-family");
const lineHeightSelect = document.getElementById("doc-line-height");
const listSelect = document.getElementById("doc-list-style");
const colorInput = document.getElementById("doc-color");
const recentColorSelect = document.getElementById("doc-color-recent");
const imageInput = document.getElementById("doc-image-input");
const tableStyleSelect = document.getElementById("doc-table-style");
const findInput = document.getElementById("doc-find");
const replaceInput = document.getElementById("doc-replace");
const sharePicker = document.getElementById("doc-share-picker");
const shareInput = document.getElementById("doc-share-input");
const shareToggle = document.getElementById("doc-share-toggle");
const shareList = document.getElementById("doc-share-list");
const shareEmpty = document.getElementById("doc-share-empty");

const RECENT_COLORS_KEY = "online_docs_recent_colors";
const MAX_RECENT_COLORS = 8;

const normalizeHexColor = (value) => {
    if (!value) return "";
    const trimmed = value.trim();
    if (!trimmed.startsWith("#")) return trimmed.toLowerCase();
    return trimmed.length === 4
        ? `#${trimmed[1]}${trimmed[1]}${trimmed[2]}${trimmed[2]}${trimmed[3]}${trimmed[3]}`.toLowerCase()
        : trimmed.toLowerCase();
};

const loadRecentColors = () => {
    try {
        const raw = localStorage.getItem(RECENT_COLORS_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return [];
    }
};

const saveRecentColors = (colors) => {
    try {
        localStorage.setItem(RECENT_COLORS_KEY, JSON.stringify(colors));
    } catch (error) {
        // ignore storage errors
    }
};

const updateRecentColorSelect = (colors) => {
    if (!recentColorSelect) return;
    const options = Array.from(recentColorSelect.options).filter(
        (option) => option.value,
    );
    options.forEach((option) => option.remove());
    colors.forEach((color) => {
        const option = document.createElement("option");
        option.value = color;
        option.textContent = " "; // show swatch instead of hex text
        option.style.backgroundColor = color;
        option.style.color = color;
        recentColorSelect.appendChild(option);
    });
};

const rememberColor = (value) => {
    const normalized = normalizeHexColor(value);
    if (!normalized || !normalized.startsWith("#")) return;
    const colors = loadRecentColors();
    const filtered = colors.filter((color) => color !== normalized);
    filtered.unshift(normalized);
    const next = filtered.slice(0, MAX_RECENT_COLORS);
    saveRecentColors(next);
    updateRecentColorSelect(next);
};

const TextStyleEx = TextStyle.extend({
    addAttributes() {
        return {
            fontSize: {
                default: null,
                parseHTML: (element) =>
                    element.style.fontSize?.replace(/['"]/g, "") || null,
                renderHTML: (attributes) => {
                    if (!attributes.fontSize) {
                        return {};
                    }
                    return { style: `font-size: ${attributes.fontSize}` };
                },
            },
            fontFamily: {
                default: null,
                parseHTML: (element) =>
                    element.style.fontFamily?.replace(/['"]/g, "") || null,
                renderHTML: (attributes) => {
                    if (!attributes.fontFamily) {
                        return {};
                    }
                    const needsQuotes = /\s/.test(attributes.fontFamily);
                    const family = needsQuotes
                        ? `"${attributes.fontFamily}"`
                        : attributes.fontFamily;
                    return { style: `font-family: ${family}` };
                },
            },
        };
    },
});

const LineHeight = Extension.create({
    name: "lineHeight",
    addGlobalAttributes() {
        return [
            {
                types: ["paragraph", "heading"],
                attributes: {
                    lineHeight: {
                        default: null,
                        parseHTML: (element) =>
                            element.style.lineHeight?.replace(/['"]/g, "") ||
                            null,
                        renderHTML: (attributes) => {
                            if (!attributes.lineHeight) {
                                return {};
                            }
                            return {
                                style: `line-height: ${attributes.lineHeight}`,
                            };
                        },
                    },
                },
            },
        ];
    },
});

const TableEx = Table.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            tableStyle: {
                default: "grid",
                parseHTML: (element) =>
                    element.getAttribute("data-table-style") || "grid",
                renderHTML: (attributes) => {
                    const value = attributes.tableStyle || "grid";
                    return {
                        "data-table-style": value,
                        class: `table-${value}`,
                    };
                },
            },
        };
    },
});

const normalizeFontFamily = (fontFamily) => {
    if (!fontFamily) return "";
    const first = fontFamily.split(",")[0]?.trim() || "";
    return first.replace(/['"]/g, "");
};

const updateFontFamilySelect = (select, family) => {
    if (!select) return;
    const normalized = normalizeFontFamily(family);
    const options = Array.from(select.options);
    options
        .filter((option) => option.dataset?.dynamic === "true")
        .forEach((option) => option.remove());

    if (!normalized) {
        select.value = "";
        return;
    }

    const existing = options.find((option) => option.value === normalized);
    if (existing) {
        select.value = normalized;
        return;
    }

    const dynamicOption = document.createElement("option");
    dynamicOption.value = normalized;
    dynamicOption.textContent = normalized;
    dynamicOption.dataset.dynamic = "true";
    select.add(dynamicOption, 1);
    select.value = normalized;
};

const getSelectionFontFamily = (editor) => {
    try {
        const { state, view } = editor;
        const { from } = state.selection;
        const dom = view.domAtPos(from).node;
        const element =
            dom.nodeType === Node.TEXT_NODE ? dom.parentElement : dom;
        if (!element || !(element instanceof HTMLElement)) {
            return "";
        }
        return window.getComputedStyle(element).fontFamily || "";
    } catch (error) {
        return "";
    }
};

const updateToolbarState = (editor) => {
    if (!toolbar) return;
    const buttons = toolbar.querySelectorAll("button[data-command]");
    buttons.forEach((button) => {
        const command = button.dataset.command;
        let active = false;

        switch (command) {
            case "bold":
                active = editor.isActive("bold");
                break;
            case "italic":
                active = editor.isActive("italic");
                break;
            case "underline":
                active = editor.isActive("underline");
                break;
            case "strike":
                active = editor.isActive("strike");
                break;
            case "blockquote":
                active = editor.isActive("blockquote");
                break;
            case "bulletList":
                active = editor.isActive("bulletList");
                break;
            case "orderedList":
                active = editor.isActive("orderedList");
                break;
            case "code":
                active = editor.isActive("code");
                break;
            case "codeBlock":
                active = editor.isActive("codeBlock");
                break;
            case "alignLeft":
                active = editor.isActive({ textAlign: "left" });
                break;
            case "alignCenter":
                active = editor.isActive({ textAlign: "center" });
                break;
            case "alignRight":
                active = editor.isActive({ textAlign: "right" });
                break;
            case "alignJustify":
                active = editor.isActive({ textAlign: "justify" });
                break;
            case "highlight":
                active = editor.isActive("highlight");
                break;
            case "table":
                active = editor.isActive("table");
                break;
            default:
                active = false;
        }

        button.classList.toggle("doc-toolbar-active", active);
    });

    if (headingSelect) {
        if (editor.isActive("heading", { level: 1 })) {
            headingSelect.value = "h1";
        } else if (editor.isActive("heading", { level: 2 })) {
            headingSelect.value = "h2";
        } else if (editor.isActive("heading", { level: 3 })) {
            headingSelect.value = "h3";
        } else {
            headingSelect.value = "paragraph";
        }
    }

    if (fontSizeSelect) {
        const currentSize = editor.getAttributes("textStyle")?.fontSize || "";
        fontSizeSelect.value = currentSize;
    }

    if (fontFamilySelect) {
        const currentFamily =
            editor.getAttributes("textStyle")?.fontFamily || "";
        const computedFamily = getSelectionFontFamily(editor);
        updateFontFamilySelect(
            fontFamilySelect,
            currentFamily || computedFamily,
        );
    }

    if (lineHeightSelect) {
        const currentLineHeight =
            editor.getAttributes("heading")?.lineHeight ||
            editor.getAttributes("paragraph")?.lineHeight ||
            "";
        lineHeightSelect.value = currentLineHeight;
    }

    if (listSelect) {
        if (editor.isActive("bulletList")) {
            listSelect.value = "bullet";
        } else if (editor.isActive("orderedList")) {
            listSelect.value = "ordered";
        } else {
            listSelect.value = "";
        }
    }

    if (tableStyleSelect) {
        if (editor.isActive("table")) {
            const style = editor.getAttributes("table")?.tableStyle || "grid";
            tableStyleSelect.value = style;
        } else {
            tableStyleSelect.value = "";
        }
    }
};

const findNextMatch = (editor, query) => {
    if (!query) return null;
    const q = query.toLowerCase();
    const { doc, selection } = editor.state;
    const start = selection.to;
    let found = null;

    doc.descendants((node, pos) => {
        if (found || !node.isText) return false;
        const text = node.text || "";
        const lower = text.toLowerCase();
        const fromIndex = Math.max(0, start - pos);
        const idx = lower.indexOf(q, fromIndex);
        if (idx !== -1) {
            found = { from: pos + idx, to: pos + idx + query.length };
            return false;
        }
        return true;
    });

    if (found) return found;

    doc.descendants((node, pos) => {
        if (found || !node.isText) return false;
        const text = node.text || "";
        const lower = text.toLowerCase();
        const idx = lower.indexOf(q);
        if (idx !== -1) {
            found = { from: pos + idx, to: pos + idx + query.length };
            return false;
        }
        return true;
    });

    return found;
};

const replaceAllMatches = (editor, query, replacement) => {
    if (!query) return;
    const q = query.toLowerCase();
    const { doc, schema } = editor.state;
    const matches = [];

    doc.descendants((node, pos) => {
        if (!node.isText) return true;
        const text = node.text || "";
        const lower = text.toLowerCase();
        let idx = 0;
        while ((idx = lower.indexOf(q, idx)) !== -1) {
            matches.push({
                from: pos + idx,
                to: pos + idx + query.length,
                marks: node.marks,
            });
            idx += query.length;
        }
        return true;
    });

    if (!matches.length) return;

    let tr = editor.state.tr;
    matches.reverse().forEach((match) => {
        tr = tr.replaceWith(
            match.from,
            match.to,
            schema.text(replacement, match.marks),
        );
    });
    editor.view.dispatch(tr);
};

const clampNumber = (value, min, max) => {
    const parsed = Number(value);
    if (Number.isNaN(parsed)) return min;
    return Math.min(Math.max(parsed, min), max);
};

const insertSpreadsheet = (editor, rows = 12, cols = 8) => {
    const safeRows = clampNumber(rows, 1, 40);
    const safeCols = clampNumber(cols, 1, 20);
    editor
        .chain()
        .focus()
        .insertTable({ rows: safeRows, cols: safeCols, withHeaderRow: true })
        .updateAttributes("table", { tableStyle: "sheet" })
        .run();
};

if (editorRoot && form && contentField) {
    const titleInput = form.querySelector('input[name="title"]');
    const saveUrl = form.getAttribute("action");
    let saveTimer = null;

    const sanitizeHtml = (html) =>
        DOMPurify.sanitize(html, {
            ADD_ATTR: [
                "style",
                "class",
                "colspan",
                "rowspan",
                "src",
                "alt",
                "title",
                "width",
                "height",
                "data-table-style",
            ],
            ADD_TAGS: [
                "table",
                "thead",
                "tbody",
                "tfoot",
                "tr",
                "th",
                "td",
                "colgroup",
                "col",
                "mark",
                "img",
            ],
        });

    const setStatus = (text, tone = "text-muted-400") => {
        if (!statusEl) return;
        statusEl.textContent = text;
        statusEl.className = `text-xs ${tone}`;
    };

    const isReadOnly = form.dataset.readOnly === "true";
    const readOnlyText = form.dataset.readOnlyText || "Read only";

    const editor = new Editor({
        element: editorRoot,
        editable: !isReadOnly,
        content: contentField.value || "",
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [1, 2, 3],
                },
            }),
            Underline,
            TextStyleEx,
            LineHeight,
            Color,
            Highlight,
            Image.configure({
                allowBase64: true,
            }),
            TextAlign.configure({
                types: ["heading", "paragraph"],
            }),
            TableEx.configure({
                resizable: true,
            }),
            TableRow,
            TableHeader,
            TableCell,
        ],
        onUpdate: ({ editor: editorInstance }) => {
            updateToolbarState(editorInstance);
            if (isReadOnly) return;
            if (!saveUrl) return;
            if (saveTimer) {
                clearTimeout(saveTimer);
            }
            saveTimer = setTimeout(async () => {
                try {
                    setStatus(form.dataset.savingText || "Saving...");
                    const sanitized = sanitizeHtml(
                        editorInstance.getHTML() || "",
                    );
                    const payload = {
                        title: titleInput ? titleInput.value : "",
                        content: sanitized,
                    };
                    await axios.put(saveUrl, payload, {
                        headers: {
                            Accept: "application/json",
                        },
                    });
                    contentField.value = sanitized;
                    setStatus(
                        form.dataset.savedText || "Saved",
                        "text-emerald-600",
                    );
                } catch (error) {
                    setStatus("Save failed", "text-danger");
                    console.error(error);
                }
            }, 1200);
        },
    });

    if (isReadOnly) {
        setStatus(readOnlyText);
    }

    updateToolbarState(editor);

    if (recentColorSelect) {
        updateRecentColorSelect(loadRecentColors());
    }

    if (sharePicker && shareInput && shareList) {
        const shareOptions = Array.from(
            shareList.querySelectorAll(".doc-share-option"),
        );

        const showShareList = () => {
            shareList.classList.remove("hidden");
        };

        const hideShareList = () => {
            shareList.classList.add("hidden");
        };

        const filterShareOptions = (query) => {
            const q = (query || "").trim().toLowerCase();
            let visible = 0;
            shareOptions.forEach((option) => {
                const label = (option.dataset.label || "").toLowerCase();
                const value = (option.dataset.value || "").toLowerCase();
                const match = !q || label.includes(q) || value.includes(q);
                option.classList.toggle("hidden", !match);
                if (match) {
                    visible += 1;
                }
            });
            if (shareEmpty) {
                shareEmpty.classList.toggle("hidden", visible > 0);
            }
        };

        shareInput.addEventListener("focus", () => {
            filterShareOptions(shareInput.value);
            showShareList();
        });

        shareInput.addEventListener("input", () => {
            filterShareOptions(shareInput.value);
            showShareList();
        });

        if (shareToggle) {
            shareToggle.addEventListener("click", () => {
                if (shareList.classList.contains("hidden")) {
                    filterShareOptions(shareInput.value);
                    showShareList();
                    shareInput.focus();
                } else {
                    hideShareList();
                }
            });
        }

        shareOptions.forEach((option) => {
            option.addEventListener("click", () => {
                shareInput.value =
                    option.dataset.value || option.textContent.trim();
                hideShareList();
            });
        });

        document.addEventListener("click", (event) => {
            if (!sharePicker.contains(event.target)) {
                hideShareList();
            }
        });
    }

    editor.on("selectionUpdate", ({ editor: editorInstance }) => {
        updateToolbarState(editorInstance);
    });

    if (titleInput && !isReadOnly) {
        titleInput.addEventListener("input", () => editor.commands.focus());
        titleInput.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                event.preventDefault();
                editor.commands.focus();
            }
        });
    }

    form.addEventListener("submit", () => {
        const sanitized = sanitizeHtml(editor.getHTML() || "");
        contentField.value = sanitized;
    });

    if (toolbar && !isReadOnly) {
        toolbar.addEventListener("click", (event) => {
            const button = event.target.closest("button[data-command]");
            if (!button) return;
            const command = button.dataset.command;

            switch (command) {
                case "bold":
                    editor.chain().focus().toggleBold().run();
                    updateToolbarState(editor);
                    break;
                case "italic":
                    editor.chain().focus().toggleItalic().run();
                    updateToolbarState(editor);
                    break;
                case "underline":
                    editor.chain().focus().toggleUnderline().run();
                    updateToolbarState(editor);
                    break;
                case "strike":
                    editor.chain().focus().toggleStrike().run();
                    updateToolbarState(editor);
                    break;
                case "blockquote":
                    editor.chain().focus().toggleBlockquote().run();
                    updateToolbarState(editor);
                    break;
                case "image":
                    if (imageInput) {
                        imageInput.click();
                    }
                    break;
                case "bulletList":
                    editor.chain().focus().toggleBulletList().run();
                    updateToolbarState(editor);
                    break;
                case "orderedList":
                    editor.chain().focus().toggleOrderedList().run();
                    updateToolbarState(editor);
                    break;
                case "code":
                    editor.chain().focus().toggleCode().run();
                    updateToolbarState(editor);
                    break;
                case "codeBlock":
                    editor.chain().focus().toggleCodeBlock().run();
                    updateToolbarState(editor);
                    break;
                case "alignLeft":
                    editor.chain().focus().setTextAlign("left").run();
                    updateToolbarState(editor);
                    break;
                case "alignCenter":
                    editor.chain().focus().setTextAlign("center").run();
                    updateToolbarState(editor);
                    break;
                case "alignRight":
                    editor.chain().focus().setTextAlign("right").run();
                    updateToolbarState(editor);
                    break;
                case "alignJustify":
                    editor.chain().focus().setTextAlign("justify").run();
                    updateToolbarState(editor);
                    break;
                case "highlight":
                    editor.chain().focus().toggleHighlight().run();
                    updateToolbarState(editor);
                    break;
                case "table":
                    editor
                        .chain()
                        .focus()
                        .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
                        .updateAttributes("table", { tableStyle: "grid" })
                        .run();
                    updateToolbarState(editor);
                    break;
                case "excel":
                    insertSpreadsheet(editor, 12, 8);
                    updateToolbarState(editor);
                    break;
                case "addRow":
                    editor.chain().focus().addRowAfter().run();
                    updateToolbarState(editor);
                    break;
                case "addColumn":
                    editor.chain().focus().addColumnAfter().run();
                    updateToolbarState(editor);
                    break;
                case "deleteRow":
                    editor.chain().focus().deleteRow().run();
                    updateToolbarState(editor);
                    break;
                case "deleteColumn":
                    editor.chain().focus().deleteColumn().run();
                    updateToolbarState(editor);
                    break;
                case "deleteTable":
                    editor.chain().focus().deleteTable().run();
                    updateToolbarState(editor);
                    break;
                case "findNext":
                    if (!findInput) return;
                    if (!findInput.value.trim()) return;
                    {
                        const match = findNextMatch(
                            editor,
                            findInput.value.trim(),
                        );
                        if (match) {
                            editor.commands.setTextSelection(match);
                            editor.commands.focus();
                        }
                    }
                    break;
                case "replace":
                    if (!findInput || !replaceInput) return;
                    if (!findInput.value.trim()) return;
                    {
                        const { empty } = editor.state.selection;
                        if (!empty) {
                            editor
                                .chain()
                                .focus()
                                .insertContent(replaceInput.value)
                                .run();
                        } else {
                            const match = findNextMatch(
                                editor,
                                findInput.value.trim(),
                            );
                            if (match) {
                                editor.commands.setTextSelection(match);
                                editor
                                    .chain()
                                    .focus()
                                    .insertContent(replaceInput.value)
                                    .run();
                            }
                        }
                    }
                    break;
                case "replaceAll":
                    if (!findInput || !replaceInput) return;
                    if (!findInput.value.trim()) return;
                    replaceAllMatches(
                        editor,
                        findInput.value.trim(),
                        replaceInput.value,
                    );
                    updateToolbarState(editor);
                    break;
                default:
                    break;
            }
        });
    }

    if (headingSelect && !isReadOnly) {
        headingSelect.addEventListener("change", (event) => {
            const value = event.target.value;
            if (value === "paragraph") {
                editor.chain().focus().setParagraph().run();
            } else {
                const level = Number(value.replace("h", ""));
                editor.chain().focus().toggleHeading({ level }).run();
            }
            updateToolbarState(editor);
        });
    }

    if (fontSizeSelect && !isReadOnly) {
        fontSizeSelect.addEventListener("change", (event) => {
            const value = event.target.value;
            if (!value) {
                editor
                    .chain()
                    .focus()
                    .setMark("textStyle", { fontSize: null })
                    .run();
            } else {
                editor
                    .chain()
                    .focus()
                    .setMark("textStyle", { fontSize: value })
                    .run();
            }
            updateToolbarState(editor);
        });
    }

    if (fontFamilySelect && !isReadOnly) {
        fontFamilySelect.addEventListener("change", (event) => {
            const value = event.target.value;
            if (!value) {
                editor
                    .chain()
                    .focus()
                    .setMark("textStyle", { fontFamily: null })
                    .run();
            } else {
                editor
                    .chain()
                    .focus()
                    .setMark("textStyle", { fontFamily: value })
                    .run();
            }
            updateToolbarState(editor);
        });
    }

    if (lineHeightSelect && !isReadOnly) {
        lineHeightSelect.addEventListener("change", (event) => {
            const value = event.target.value;
            const next = value || null;
            if (editor.isActive("heading")) {
                editor
                    .chain()
                    .focus()
                    .updateAttributes("heading", { lineHeight: next })
                    .run();
            } else {
                editor
                    .chain()
                    .focus()
                    .updateAttributes("paragraph", { lineHeight: next })
                    .run();
            }
            updateToolbarState(editor);
        });
    }

    if (listSelect && !isReadOnly) {
        listSelect.addEventListener("change", (event) => {
            const value = event.target.value;
            if (value === "bullet") {
                editor.chain().focus().toggleBulletList().run();
            } else if (value === "ordered") {
                editor.chain().focus().toggleOrderedList().run();
            } else {
                if (editor.isActive("bulletList")) {
                    editor.chain().focus().toggleBulletList().run();
                }
                if (editor.isActive("orderedList")) {
                    editor.chain().focus().toggleOrderedList().run();
                }
            }
            updateToolbarState(editor);
        });
    }

    if (colorInput && !isReadOnly) {
        colorInput.addEventListener("input", (event) => {
            editor.chain().focus().setColor(event.target.value).run();
            rememberColor(event.target.value);
            updateToolbarState(editor);
        });
    }

    if (imageInput && !isReadOnly) {
        imageInput.addEventListener("change", (event) => {
            const file = event.target.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                editor
                    .chain()
                    .focus()
                    .setImage({ src: reader.result, alt: file.name })
                    .run();
                updateToolbarState(editor);
            };
            reader.readAsDataURL(file);
            event.target.value = "";
        });
    }

    if (tableStyleSelect && !isReadOnly) {
        tableStyleSelect.addEventListener("change", (event) => {
            const value = event.target.value;
            if (!value) return;
            editor.commands.updateAttributes("table", { tableStyle: value });
            updateToolbarState(editor);
        });
    }

    if (recentColorSelect && !isReadOnly) {
        recentColorSelect.addEventListener("change", (event) => {
            const value = event.target.value;
            if (!value) return;
            editor.chain().focus().setColor(value).run();
            rememberColor(value);
            updateToolbarState(editor);
        });
    }
}
