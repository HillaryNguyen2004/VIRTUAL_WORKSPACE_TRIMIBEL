document.addEventListener("DOMContentLoaded", () => {
    const pwd = document.getElementById("password");
    const pwd_confirmation = document.getElementById("password_confirmation");
    const validatePwdBlock = document.getElementById("validate-pwd");
    const atLeast8Words = document.getElementById("at-least-8-words");
    const atLeast1SpeChar = document.getElementById("at-least-1-spe-char");
    const atLeast1Number = document.getElementById("at-least-1-number");
    const pwd_match = document.getElementById("pwd-match");

    const numberRegex = /\d/;
    const specialCharRegex = /[^A-Za-z0-9]/;

    const toggle = (el, ok) => {
        el.classList.toggle("text-[#5AE194]", ok);
        el.classList.toggle("text-gray-300", !ok);
    };

    const validatePwd = () => {
        const v = pwd.value || "";
        toggle(atLeast8Words, v.length >= 8);
        toggle(atLeast1SpeChar, specialCharRegex.test(v));
        toggle(atLeast1Number, numberRegex.test(v));
    }

    const validateMatch = () => {
        const a = pwd.value || "";
        const b = pwd_confirmation?.value ?? "";
        const bothTyped = a.length || b.length;
        const ok = a === b && b.length > 0;

        if (pwd_match) {
            pwd_match.classList.toggle("text-[#5AE194]", ok);
            pwd_match.classList.toggle("text-rose-500", bothTyped && !ok);
            pwd_match.classList.toggle("text-gray-300", !bothTyped);
        }

        if (pwd_confirmation)
            pwd_confirmation.setAttribute(
                "aria-invalid",
                bothTyped && !ok ? "true" : "false"
            );
    }

    const update = () => {
        const hasPwd = 1;

        // show/hide blocks
        validatePwdBlock?.classList.toggle("hidden", !hasPwd);
    };

    pwd?.addEventListener("input", () => {
        update();
        validatePwd();
    });

    pwd_confirmation?.addEventListener("input", () => {
        update();
        validateMatch();
    });

    // run once
    validatePwd();
    // validateMatch();
    // update();
});
