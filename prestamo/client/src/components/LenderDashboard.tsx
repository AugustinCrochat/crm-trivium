import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { API } from '../config/api';

interface DashboardData {
  totalInvested: number;
  totalToReceive: number;
  upcomingPayments: Array<{
    id: number;
    amount: number;
    due_date: string;
    status: string;
  }>;
}

const LenderDashboard = () => {
  const { id } = useParams();
  const [data, setData] = useState<DashboardData | null>(null);

  useEffect(() => {
    fetch(API.lenderDashboard(Number(id)))
      .then(res => res.json())
      .then(setData)
      .catch(console.error);
  }, [id]);

  if (!data) return <p>Cargando...</p>;

  const formatDate = (dateStr: string) => {
    const [year, month, day] = dateStr.split('-');
    return `${day}/${month}/${year}`;
  };

  return (
    <div className="animate-fade-in">
      <h2>Mi Perfil de Inversión</h2>
      
      <div className="grid">
        <div className="card" style={{ borderLeft: '4px solid var(--primary)' }}>
          <p style={{ color: 'var(--text-muted)' }}>Monto Total Invertido</p>
          <h2 style={{ fontSize: '2rem' }}>${data.totalInvested.toLocaleString()}</h2>
        </div>
        
        <div className="card" style={{ borderLeft: '4px solid var(--success)' }}>
          <p style={{ color: 'var(--text-muted)' }}>Monto Total a Recibir</p>
          <h2 style={{ fontSize: '2rem' }}>${data.totalToReceive.toLocaleString(undefined, { minimumFractionDigits: 2 })}</h2>
        </div>
      </div>

      <div className="card">
        <h3>Próximos Pagos a Recibir</h3>
        {data.upcomingPayments.length === 0 ? (
          <p style={{ textAlign: 'center', padding: '2rem', color: 'var(--text-muted)' }}>
            No tienes pagos pendientes por cobrar.
          </p>
        ) : (
          <table>
            <thead>
              <tr>
                <th>Fecha de cobro</th>
                <th>Monto</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              {data.upcomingPayments.map(p => (
                <tr key={p.id}>
                  <td>{formatDate(p.due_date)}</td>
                  <td>${p.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                  <td>
                    <span className={`badge ${p.status === 'paid' ? 'badge-paid' : 'badge-pending'}`}>
                      {p.status === 'paid' ? 'Pagado' : 'Pendiente'}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
};

export default LenderDashboard;
