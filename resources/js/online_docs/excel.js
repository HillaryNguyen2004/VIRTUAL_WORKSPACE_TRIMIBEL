import "luckysheet/dist/plugins/plugins.css";
import "luckysheet/dist/css/luckysheet.css";
import "luckysheet/dist/assets/iconfont/iconfont.css";
import $ from "jquery";

window.$ = $;
window.jQuery = $;

const boot = async () => {
    const container = document.getElementById("excel-editor");
    if (!container) {
        return;
    }

    const mousewheel = (await import("jquery-mousewheel")).default;
    if (typeof mousewheel === "function") {
        mousewheel($);
    }
    const luckysheet = (await import("luckysheet/dist/luckysheet.esm.js")).default;
    const LuckyExcel = (await import("luckyexcel/dist/luckyexcel.esm.js")).default;
    if (!luckysheet || !LuckyExcel) {
        console.error("Luckysheet failed to load", { luckysheet, LuckyExcel });
        return;
    }

    const saveButton = document.getElementById("excel-save");
    const statusEl = document.getElementById("excel-save-status");
    const xlsxUrl = container.dataset.xlsxUrl;
    const saveUrl = container.dataset.saveUrl;
    const csrfToken = container.dataset.csrf;
    const isReadOnly = container.dataset.readOnly === "true";
    let isSaving = false;
    let autoSaveTimer = null;

    const setStatus = (text, tone = "text-muted-400") => {
        if (!statusEl) return;
        statusEl.textContent = text;
        statusEl.className = `text-xs ${tone}`;
    };

    const createSheet = (exportJson) => {
        const data = exportJson?.sheets || [];
        luckysheet.create({
            container: "excel-editor",
            data,
            showinfobar: false,
            showsheetbar: true,
            showtoolbar: !isReadOnly,
            allowEdit: !isReadOnly,
        });
    };

    const loadWorkbook = async () => {
        try {
            const response = await fetch(xlsxUrl);
            const buffer = await response.arrayBuffer();
            const blob = new Blob([buffer], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            });
            const file = new File([blob], "sheet.xlsx");

            LuckyExcel.transformExcelToLucky(file, (exportJson) => {
                createSheet(exportJson);
            });
        } catch (error) {
            setStatus("Load failed", "text-danger");
            console.error(error);
        }
    };

    const saveWorkbook = async () => {
        if (!saveUrl || isReadOnly || isSaving) {
            return;
        }

        try {
            isSaving = true;
            setStatus(container.dataset.savingText || "Saving...");
            const sheets = luckysheet.getAllSheets();
            const response = await fetch(saveUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({ sheets }),
            });

            if (!response.ok) {
                throw new Error("Save failed");
            }

            setStatus(container.dataset.savedText || "Saved", "text-emerald-600");
        } catch (error) {
            setStatus("Save failed", "text-danger");
            console.error(error);
        } finally {
            isSaving = false;
        }
    };

    if (saveButton) {
        saveButton.addEventListener("click", saveWorkbook);
    }

    if (!isReadOnly) {
        // Keep Excel edits persisted even when the Save button is hidden in simplified UI.
        autoSaveTimer = window.setInterval(saveWorkbook, 4000);
        window.addEventListener("beforeunload", () => {
            if (autoSaveTimer) {
                clearInterval(autoSaveTimer);
                autoSaveTimer = null;
            }
            saveWorkbook();
        });
    }

    loadWorkbook();
};

boot();
