const sqlite3 = require('sqlite3').verbose();
const db = new sqlite3.Database('./server/database.sqlite');

db.all("SELECT * FROM config", (err, rows) => {
  console.log("Config:", rows);
  db.all("SELECT * FROM lenders", (err, rows) => {
    console.log("Lenders:", rows);
    db.all("SELECT * FROM loans", (err, rows) => {
      console.log("Loans:", rows);
      db.close();
    });
  });
});
