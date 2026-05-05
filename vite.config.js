import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import fg from "fast-glob";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // CSS
                "resources/css/app.css",
                "resources/css/dashboard.css",

                // Core JS
                "resources/js/app.js",
                "resources/js/submit-form.js",
                "resources/js/toggle_pwd.js",
                "resources/js/toggle_confirm_pwd.js",
                "resources/js/toggle_view.js",
                "resources/js/show-toast.js",
                "resources/js/validate_pwd.js",
                "resources/js/task_assignee_filter.js",
                "resources/js/toggle_update_phase.js",
                "resources/js/face-checkin.js",
                "resources/js/chat_bot.js",

                // Admin JS
                "resources/js/admin/toggle_update_user.js",
                "resources/js/admin/edit_holiday.js",
                "resources/js/admin/edit_company_hours.js",
                "resources/js/admin/close_info_alert.js",
                "resources/js/admin/monthly_attendance_charts.js",

                // Dashboard layout
                "resources/js/dashboard_layout/switch_lang.js",
                "resources/js/dashboard_layout/dropdown_profile.js",
                "resources/js/dashboard_layout/toggle_sidebar.js",
                "resources/js/dashboard_layout/dropdown_notification.js",
                "resources/js/dashboard_layout/scroll_to_top.js",

                // User dashboard components
                "resources/js/user_dashboard/team_member_dialog.js",
                "resources/js/user_dashboard/task_dialog.js",
                "resources/js/user_dashboard/show_task_description.js",
                "resources/js/user_dashboard/update_status.js",
                "resources/js/user_dashboard/team_dialog.js",

                // Request dayoff
                "resources/js/request_dayoff/request_dayoff_dialog.js",

                // Settings
                "resources/js/settings/upload_image.js",
                "resources/js/settings/update_detail.js",

                // Video chat
                "resources/js/video-chat.js",

                // Online docs
                "resources/js/online_docs/storage.js",
                "resources/js/online_docs/editor.js",
                "resources/js/online_docs/excel.js",
                "resources/js/online_docs/chunked-uploader.js",

                // Utils (auto-scanned)
                ...fg.sync("resources/utils/**/*.js"),

                // SCSS (for layouts/app.blade.php)
                "resources/sass/app.scss",
            ],
            refresh: true,
        }),
    ],
    server: {
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
        hmr: {
            host: "localhost",
            protocol: "ws",
        },
        cors: {
            origin: [
                "https://unperceptible-genevie-surmisedly.ngrok-free.dev",
                "https://do-an-chuyen-nganh-rho.vercel.app",
                "http://localhost:8000",
                "http://127.0.0.1:8000",
            ],
            credentials: true,
        },
    },
});