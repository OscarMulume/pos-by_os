use tauri::Manager;

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .setup(|app| {
            // Configuration specifique pour Windows
            #[cfg(desktop)]
            {
                let window = app.get_webview_window("main").unwrap();
                // Definir la taille minimale
                window.set_min_size(Some(tauri::LogicalSize::new(1024, 700)))?;
            }
            Ok(())
        })
        .run(tauri::generate_context!())
        .expect("error while running POS Pro");
}
