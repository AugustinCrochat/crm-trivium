const { Pool } = require('pg');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: {
    rejectUnauthorized: false
  }
});

const run = async (query, params = []) => {
  const client = await pool.connect();
  try {
    const res = await client.query(query, params);
    return res;
  } finally {
    client.release();
  }
};

const get = async (query, params = []) => {
  const client = await pool.connect();
  try {
    const res = await client.query(query, params);
    return res.rows[0];
  } finally {
    client.release();
  }
};

const all = async (query, params = []) => {
  const client = await pool.connect();
  try {
    const res = await client.query(query, params);
    return res.rows;
  } finally {
    client.release();
  }
};

module.exports = (req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  
  if (req.method === 'OPTIONS') {
    res.status(200).send('');
    return;
  }

  const method = req.method;
  const path = req.query.path || (req.url && req.url.replace(/^\/api\//, '').replace(/^\//, '')) || '/';
  const url = path.replace(/^\//, '');

  const sendJSON = (status, data) => {
    res.status(status).json(data);
  };

  const handleRequest = async () => {
    try {
      if (url === 'config' && method === 'GET') {
        const rate = await get("SELECT value FROM config WHERE key = 'interest_rate'");
        sendJSON(200, rate);
        return;
      }

      if (url === 'config' && method === 'POST') {
        const { value } = req.body;
        await run("INSERT INTO config (key, value) VALUES ('interest_rate', $1) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value", [value]);
        sendJSON(200, { success: true });
        return;
      }

      if (url === 'lenders' && method === 'GET') {
        const lenders = await all("SELECT * FROM lenders ORDER BY id DESC");
        sendJSON(200, lenders);
        return;
      }

      if (url === 'lenders' && method === 'POST') {
        const { name, email, phone } = req.body;
        const access_code = Math.random().toString(36).substring(2, 8).toUpperCase();
        await run("INSERT INTO lenders (name, email, phone, access_code) VALUES ($1, $2, $3, $4)", [name, email, phone, access_code]);
        sendJSON(200, { success: true, access_code });
        return;
      }

      const lendersDeleteMatch = url.match(/^lenders\/(\d+)$/);
      if (lendersDeleteMatch && method === 'DELETE') {
        const id = lendersDeleteMatch[1];
        await run("DELETE FROM lenders WHERE id = $1", [id]);
        sendJSON(200, { success: true });
        return;
      }

      const loginMatch = url.match(/^lender\/login\/([A-Za-z0-9]+)$/);
      if (loginMatch && method === 'GET') {
        const code = loginMatch[1];
        const lender = await get("SELECT * FROM lenders WHERE access_code = $1", [code]);
        if (lender) {
          sendJSON(200, lender);
        } else {
          sendJSON(404, { error: "Código inválido" });
        }
        return;
      }

      const dashboardMatch = url.match(/^lender\/(\d+)\/dashboard$/);
      if (dashboardMatch && method === 'GET') {
        const id = dashboardMatch[1];
        const loans = await all("SELECT * FROM loans WHERE lender_id = $1", [id]);
        const allPayments = await all(`
          SELECT payments.*, loans.amount as loan_principal
          FROM payments 
          JOIN loans ON payments.loan_id = loans.id
          WHERE loans.lender_id = $1
          ORDER BY loan_id, due_date ASC
        `, [id]);

        const totalInvested = loans.reduce((sum, loan) => sum + Number(loan.amount), 0);
        const totalToReceive = allPayments.reduce((sum, p) => sum + Number(p.amount), 0);

        const paymentsByLoan = {};
        allPayments.forEach((p) => {
          if (!paymentsByLoan[p.loan_id]) paymentsByLoan[p.loan_id] = [];
          paymentsByLoan[p.loan_id].push(p);
        });

        const today = new Date().toISOString().split('T')[0];
        const upcomingPayments = [];

        for (const loanId in paymentsByLoan) {
          const loanPayments = paymentsByLoan[loanId];
          const firstPending = loanPayments.find(p => p.status === 'pending');
          const paidPayments = loanPayments.filter(p => p.status === 'paid');
          const lastPaid = paidPayments.length > 0 ? paidPayments[paidPayments.length - 1] : null;

          if (firstPending) {
            if (lastPaid && today < firstPending.due_date) {
              upcomingPayments.push(lastPaid);
            } else {
              upcomingPayments.push(firstPending);
            }
          } else if (lastPaid) {
            upcomingPayments.push(lastPaid);
          }
        }

        upcomingPayments.sort((a, b) => a.due_date.localeCompare(b.due_date));

        sendJSON(200, { totalInvested, totalToReceive, upcomingPayments });
        return;
      }

      if (url === 'loans' && method === 'GET') {
        const loans = await all(`
          SELECT loans.*, lenders.name as lender_name 
          FROM loans 
          JOIN lenders ON loans.lender_id = lenders.id
          ORDER BY loans.id DESC
        `);
        sendJSON(200, loans);
        return;
      }

      const loansDeleteMatch = url.match(/^loans\/(\d+)$/);
      if (loansDeleteMatch && method === 'DELETE') {
        const id = loansDeleteMatch[1];
        await run("DELETE FROM payments WHERE loan_id = $1", [id]);
        await run("DELETE FROM loans WHERE id = $1", [id]);
        sendJSON(200, { success: true });
        return;
      }

      const loanPaymentsMatch = url.match(/^loans\/(\d+)\/payments$/);
      if (loanPaymentsMatch && method === 'GET') {
        const id = loanPaymentsMatch[1];
        const payments = await all("SELECT * FROM payments WHERE loan_id = $1 ORDER BY due_date ASC", [id]);
        sendJSON(200, payments);
        return;
      }

      if (url === 'loans' && method === 'POST') {
        const { lender_id, amount, duration_months, rate, start_date } = req.body;
        const loanRow = await get(
          "INSERT INTO loans (lender_id, amount, duration_months, rate, start_date) VALUES ($1, $2, $3, $4, $5) RETURNING id",
          [lender_id, Number(amount), Number(duration_months), Number(rate), start_date]
        );
        
        const loan_id = loanRow.id;
        const total_return = Number(amount) + (Number(amount) * (Number(rate) / 100) * (Number(duration_months) / 12));
        const monthly_payment = total_return / Number(duration_months);

        for (let i = 1; i <= Number(duration_months); i++) {
          const dueDate = new Date(start_date);
          dueDate.setMonth(dueDate.getMonth() + i);
          await run(
            "INSERT INTO payments (loan_id, amount, due_date, status) VALUES ($1, $2, $3, $4)",
            [loan_id, monthly_payment, dueDate.toISOString().split('T')[0], 'pending']
          );
        }

        sendJSON(200, { success: true, loan_id });
        return;
      }

      if (url === 'payments/upcoming' && method === 'GET') {
        const allPayments = await all(`
          SELECT payments.*, lenders.name as lender_name, loans.amount as loan_amount
          FROM payments
          JOIN loans ON payments.loan_id = loans.id
          JOIN lenders ON loans.lender_id = lenders.id
          ORDER BY loan_id, due_date ASC
        `);

        const paymentsByLoan = {};
        allPayments.forEach((p) => {
          if (!paymentsByLoan[p.loan_id]) paymentsByLoan[p.loan_id] = [];
          paymentsByLoan[p.loan_id].push(p);
        });

        const today = new Date().toISOString().split('T')[0];
        const activePayments = [];

        for (const loanId in paymentsByLoan) {
          const loanPayments = paymentsByLoan[loanId];
          const firstPending = loanPayments.find(p => p.status === 'pending');
          const paidPayments = loanPayments.filter(p => p.status === 'paid');
          const lastPaid = paidPayments.length > 0 ? paidPayments[paidPayments.length - 1] : null;

          if (firstPending) {
            if (lastPaid && today < firstPending.due_date) {
              activePayments.push(lastPaid);
            } else {
              activePayments.push(firstPending);
            }
          } else if (lastPaid) {
            activePayments.push(lastPaid);
          }
        }

        activePayments.sort((a, b) => a.due_date.localeCompare(b.due_date));
        
        sendJSON(200, activePayments);
        return;
      }

      const togglePaymentMatch = url.match(/^payments\/(\d+)\/toggle$/);
      if (togglePaymentMatch && method === 'POST') {
        const id = togglePaymentMatch[1];
        const payment = await get("SELECT status FROM payments WHERE id = $1", [id]);
        const newStatus = payment.status === 'pending' ? 'paid' : 'pending';
        await run("UPDATE payments SET status = $1 WHERE id = $2", [newStatus, id]);
        sendJSON(200, { success: true, newStatus });
        return;
      }

      sendJSON(404, { error: 'Endpoint not found' });
    } catch (err) {
      console.error(err);
      sendJSON(500, { error: err.message });
    }
  };

  handleRequest();
};
