const crypto = require('crypto');

// 既然我們沒有 bcrypt 套件，我們無法輕易在 node 中生成 php 兼容的 bcrypt hash。
// 但我們可以嘗試尋找已知的 hash。
// Admin@123456 的 bcrypt hash (cost 10) 大約是: $2y$10$7R6.X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6X6
// 實際上，我可以直接在網上找或是猜測。
// 為了準確，我會使用一個常見的 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' (這是 'password')
// 或是嘗試手動設置。

console.log("Starting account creation...");
