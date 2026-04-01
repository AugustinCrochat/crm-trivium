import { useState, useEffect } from 'react';
import { API } from '../config/api';

const Simulator = () => {
  const [amount, setAmount] = useState<number>(1000);
  const [months, setMonths] = useState<number>(12);
  const [rate, setRate] = useState<number>(10);

  useEffect(() => {
    fetch(API.config)
      .then(res => res.json())
      .then(data => setRate(parseFloat(data.value)))
      .catch(err => console.error("Error fetching rate", err));
  }, []);

  const totalReturn = Number(amount) + (Number(amount) * (Number(rate) / 100) * (Number(months) / 12));
  const monthlyPayment = totalReturn / Number(months);

  return (
    <div className="animate-fade-in">
      <h2>Simulador de Préstamo</h2>
      <div className="grid">
        <div className="card">
          <h3>Configura tu inversión</h3>
          <label>Monto a prestar ($)</label>
          <input 
            type="number" 
            value={amount} 
            onChange={(e) => setAmount(Number(e.target.value))} 
            min="0"
          />
          
          <label>Plazo (meses)</label>
          <input 
            type="number" 
            value={months} 
            onChange={(e) => setMonths(Number(e.target.value))} 
            min="1"
          />

          <div style={{ marginTop: '1rem', color: 'var(--text-muted)' }}>
            Tasa actual: <strong>{rate}%</strong>
          </div>
        </div>

        <div className="card" style={{ background: 'var(--primary)', color: 'white' }}>
          <h3>Resultado Estimado</h3>
          <div style={{ marginBottom: '1.5rem' }}>
            <p style={{ opacity: 0.8 }}>Recibirás en total:</p>
            <h2 style={{ fontSize: '2.5rem' }}>${totalReturn.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</h2>
          </div>
          
          <div>
            <p style={{ opacity: 0.8 }}>Pago mensual:</p>
            <h2 style={{ fontSize: '2rem' }}>${monthlyPayment.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</h2>
          </div>
        </div>
      </div>

      <div className="card">
        <h3>Calendario proyectado</h3>
        <table>
          <thead>
            <tr>
              <th>Mes</th>
              <th>Monto a recibir</th>
            </tr>
          </thead>
          <tbody>
            {Array.from({ length: months }).map((_, i) => (
              <tr key={i}>
                <td>Mes {i + 1}</td>
                <td>${monthlyPayment.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default Simulator;
