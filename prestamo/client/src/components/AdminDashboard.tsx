import React, { useState, useEffect } from 'react';
import { API } from '../config/api';

interface Lender {
  id: number;
  name: string;
  email: string;
  phone: string;
  access_code: string;
}

interface Loan {
  id: number;
  lender_id: number;
  lender_name: string;
  amount: number;
  duration_months: number;
  rate: number;
  start_date: string;
}

interface Payment {
  id: number;
  loan_id: number;
  lender_name?: string;
  amount: number;
  due_date: string;
  status: 'pending' | 'paid';
}

const AdminDashboard = () => {
  const [lenders, setLenders] = useState<Lender[]>([]);
  const [loans, setLoans] = useState<Loan[]>([]);
  const [payments, setPayments] = useState<Payment[]>([]);
  const [rate, setRate] = useState<string>('');
  
  // UI State
  const [expandedLoan, setExpandedLoan] = useState<number | null>(null);
  const [loanPayments, setLoanPayments] = useState<Payment[]>([]);

  // Forms
  const [newLender, setNewLender] = useState({ name: '', email: '', phone: '' });
  const [newLoan, setNewLoan] = useState({ lender_id: '', amount: '', duration_months: '', start_date: new Date().toISOString().split('T')[0] });

  const fetchData = async () => {
    try {
      const [configRes, lendersRes, loansRes, paymentsRes] = await Promise.all([
        fetch(API.config),
        fetch(API.lenders),
        fetch(API.loans),
        fetch(`${API.payments}/upcoming`)
      ]);
      const config = await configRes.json();
      setRate(config.value);
      setLenders(await lendersRes.json());
      setLoans(await loansRes.json());
      setPayments(await paymentsRes.json());
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const fetchLoanPayments = async (loanId: number) => {
    if (expandedLoan === loanId) {
      setExpandedLoan(null);
      return;
    }
    const res = await fetch(API.loanPayments(loanId));
    setLoanPayments(await res.json());
    setExpandedLoan(loanId);
  };

  const handleUpdateRate = async () => {
    await fetch(API.config, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ value: rate })
    });
    alert('Tasa actualizada');
  };

  const handleAddLender = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(API.lenders, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newLender)
      });
      if (res.ok) {
        setNewLender({ name: '', email: '', phone: '' });
        fetchData();
      }
    } catch (err) {
      console.error("Error adding lender", err);
    }
  };

  const handleCreateLoan = async (e: React.FormEvent) => {
    e.preventDefault();
    await fetch(API.loans, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        ...newLoan,
        rate: parseFloat(rate)
      })
    });
    setNewLoan({ lender_id: '', amount: '', duration_months: '', start_date: new Date().toISOString().split('T')[0] });
    fetchData();
  };

  const handleDeleteLoan = async (id: number) => {
    if (!confirm('¿Estás seguro de cancelar este préstamo? Todos los pagos asociados se eliminarán.')) return;
    const res = await fetch(`${API.loans}/${id}`, { method: 'DELETE' });
    if (!res.ok) {
      const error = await res.json();
      alert('Error al eliminar: ' + error.error);
      return;
    }
    setExpandedLoan(null);
    fetchData();
  };

  const handleDeleteLender = async (id: number) => {
    if (!confirm('¿Estás seguro de eliminar este prestamista? Se eliminarán TODOS sus préstamos y pagos asociados.')) return;
    await fetch(`${API.lenders}/${id}`, { method: 'DELETE' });
    fetchData();
  };

  const togglePayment = async (id: number) => {
    await fetch(API.togglePayment(id), { method: 'POST' });
    if (expandedLoan) {
      const res = await fetch(API.loanPayments(expandedLoan));
      setLoanPayments(await res.json());
    }
    fetchData();
  };

  const formatDate = (dateStr: string) => {
    const [year, month, day] = dateStr.split('-');
    return `${day}/${month}/${year}`;
  };

  // Group payments by loan and pick the next one
  const getNextPayments = () => {
    const next: Payment[] = [];
    const loanIdsSeen = new Set();
    
    // Sort payments by date to ensure the first one we see is the "next"
    const sortedPayments = [...payments].sort((a, b) => a.due_date.localeCompare(b.due_date));
    
    for (const p of sortedPayments) {
      if (!loanIdsSeen.has(p.loan_id)) {
        next.push(p);
        loanIdsSeen.add(p.loan_id);
      }
    }
    return next;
  };

  return (
    <div className="animate-fade-in">
      <h2>Panel de Administración</h2>
      
      <div className="grid">
        <div className="card">
          <h3>Configuración Global</h3>
          <label>Tasa de Interés (%)</label>
          <div style={{ display: 'flex', gap: '1rem' }}>
            <input 
              type="number" 
              value={rate} 
              onChange={(e) => setRate(e.target.value)} 
              style={{ marginBottom: 0 }}
            />
            <button onClick={handleUpdateRate}>Guardar</button>
          </div>
        </div>

        <div className="card">
          <h3>Registrar Prestamista</h3>
          <form onSubmit={handleAddLender}>
            <input 
              placeholder="Nombre" 
              value={newLender.name} 
              onChange={(e) => setNewLender({...newLender, name: e.target.value})} 
              required
            />
            <input 
              placeholder="Email" 
              value={newLender.email} 
              onChange={(e) => setNewLender({...newLender, email: e.target.value})} 
            />
            <button type="submit">Agregar</button>
          </form>
        </div>
      </div>

      <div className="card">
        <h3>Prestamistas</h3>
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Código</th>
              <th>Préstamos Activos</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {lenders.map(l => (
              <tr key={l.id}>
                <td>{l.name}</td>
                <td><code style={{ background: '#eee', padding: '2px 6px', borderRadius: '4px' }}>{l.access_code}</code></td>
                <td>{loans.filter(loan => loan.lender_id === l.id).length}</td>
                <td>
                  <button 
                    style={{ background: 'var(--danger)', padding: '4px 10px', fontSize: '0.8rem' }}
                    onClick={() => handleDeleteLender(l.id)}
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="grid">
        <div className="card">
          <h3>Nuevo Préstamo</h3>
          <form onSubmit={handleCreateLoan}>
            <select 
              value={newLoan.lender_id} 
              onChange={(e) => setNewLoan({...newLoan, lender_id: e.target.value})}
              required
            >
              <option value="">Seleccionar Persona</option>
              {lenders.map(l => <option key={l.id} value={l.id}>{l.name}</option>)}
            </select>
            <input 
              type="number" 
              placeholder="Monto ($)" 
              value={newLoan.amount} 
              onChange={(e) => setNewLoan({...newLoan, amount: e.target.value})} 
              required
            />
            <input 
              type="number" 
              placeholder="Meses" 
              value={newLoan.duration_months} 
              onChange={(e) => setNewLoan({...newLoan, duration_months: e.target.value})} 
              required
            />
            <label>Fecha de inicio</label>
            <input 
              type="date" 
              value={newLoan.start_date} 
              onChange={(e) => setNewLoan({...newLoan, start_date: e.target.value})} 
              required
            />
            <button type="submit">Generar Préstamo</button>
          </form>
        </div>

        <div className="card" style={{ gridColumn: 'span 2' }}>
          <h3>Próximos Pagos por Préstamo</h3>
          <table>
            <thead>
              <tr>
                <th>Persona</th>
                <th>Monto</th>
                <th>Vencimiento</th>
                <th style={{ textAlign: 'center' }}>Estado</th>
              </tr>
            </thead>
            <tbody>
              {getNextPayments().map(p => (
                <tr key={p.id}>
                  <td>{p.lender_name}</td>
                  <td>${p.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                  <td>{formatDate(p.due_date)}</td>
                  <td style={{ textAlign: 'center' }}>
                    <button 
                      onClick={() => togglePayment(p.id)}
                      className={`badge ${p.status === 'paid' ? 'badge-paid' : 'badge-pending'}`}
                      style={{ 
                        border: 'none',
                        cursor: 'pointer',
                        width: '100px',
                        display: 'inline-block',
                        textAlign: 'center'
                      }}
                    >
                      {p.status === 'paid' ? 'Pagado' : 'Pendiente'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="card">
        <h3>Gestión de Préstamos</h3>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Persona</th>
              <th>Monto</th>
              <th>Plazo</th>
              <th>Inicio</th>
              <th>Detalles</th>
            </tr>
          </thead>
          <tbody>
            {loans.map(loan => (
              <React.Fragment key={loan.id}>
                <tr>
                  <td>#{loan.id}</td>
                  <td>{loan.lender_name}</td>
                  <td>${loan.amount.toLocaleString()}</td>
                  <td>{loan.duration_months} meses</td>
                  <td>{formatDate(loan.start_date)}</td>
                  <td style={{ display: 'flex', gap: '0.5rem' }}>
                    <button onClick={() => fetchLoanPayments(loan.id)}>
                      {expandedLoan === loan.id ? 'Cerrar' : 'Ver Pagos'}
                    </button>
                    <button 
                      style={{ background: 'var(--danger)', padding: '4px 10px', fontSize: '0.8rem' }}
                      onClick={() => handleDeleteLoan(loan.id)}
                    >
                      Eliminar
                    </button>
                  </td>
                </tr>
                {expandedLoan === loan.id && (
                  <tr>
                    <td colSpan={6} style={{ background: '#f9f9f9', padding: '1rem' }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '1rem' }}>
                        <h4>Detalle de Pagos - Préstamo #{loan.id}</h4>
                        <button 
                          style={{ background: 'var(--danger)', fontSize: '0.8rem' }}
                          onClick={() => handleDeleteLoan(loan.id)}
                        >
                          Cancelar/Eliminar Préstamo
                        </button>
                      </div>
                      <table style={{ background: 'white' }}>
                        <thead>
                          <tr>
                            <th>Mes</th>
                            <th>Monto</th>
                            <th>Vencimiento</th>
                            <th style={{ textAlign: 'center' }}>Estado</th>
                          </tr>
                        </thead>
                        <tbody>
                          {loanPayments.map((p, idx) => (
                            <tr key={p.id}>
                              <td>{idx + 1}</td>
                              <td>${p.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                              <td>{formatDate(p.due_date)}</td>
                              <td style={{ textAlign: 'center' }}>
                                <button 
                                  onClick={() => togglePayment(p.id)}
                                  className={`badge ${p.status === 'paid' ? 'badge-paid' : 'badge-pending'}`}
                                  style={{ 
                                    border: 'none',
                                    cursor: 'pointer',
                                    width: '100px',
                                    display: 'inline-block',
                                    textAlign: 'center'
                                  }}
                                >
                                  {p.status === 'paid' ? 'Pagado' : 'Pendiente'}
                                </button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </td>
                  </tr>
                )}
              </React.Fragment>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default AdminDashboard;
