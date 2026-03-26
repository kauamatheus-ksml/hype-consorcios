const fs = require('fs');
const { Client } = require('pg');

const client = new Client({
  host: 'aws-0-us-west-2.pooler.supabase.com',
  user: 'postgres.pnffxphwgqrtwmlrxwky',
  password: 'Idiasiin@kaos_',
  database: 'postgres',
  port: 5432,
  ssl: { rejectUnauthorized: false }
});

async function run() {
  await client.connect();
  console.log("Connected to Supabase successfully.");
  
  const sql = fs.readFileSync(__dirname + '/supabase_migration.sql', 'utf-8');
  console.log("Executing SQL migration...");
  
  try {
    await client.query(sql);
    console.log("Migration executed successfully!");
  } catch (err) {
    console.error("Migration failed:", err);
  } finally {
    await client.end();
  }
}

run();
