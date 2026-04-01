import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { API } from '../config/api';

const LenderLogin = () => {
  const [code, setCode] = useState('');
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    try {
      const res = await fetch(API.lenderLogin(code.toUpperCase()));
      if (res.ok) {
        const lender = await res.json();
        navigate(`/lender/${lender.id}`);
      } else {
        setError('Código de acceso incorrecto');
      }
    } catch (err) {
      setError('Error al conectar con el servidor');
    }
  };

  return (
    <div className="animate-fade-in" style={{ maxWidth: '400px', margin: '4rem auto' }}>
      <div className="card">
        <h2>Acceso Prestamista</h2>
        <p style={{ color: 'var(--text-muted)', marginBottom: '1.5rem' }}>
          Ingresa tu código de 6 dígitos para ver el estado de tus préstamos.
        </p>
        <form onSubmit={handleLogin}>
          <input 
            placeholder="CÓDIGO (Ej: A1B2C3)" 
            value={code} 
            onChange={(e) => setCode(e.target.value)}
            style={{ textAlign: 'center', fontSize: '1.5rem', letterSpacing: '2px' }}
            required
          />
          {error && <p style={{ color: 'red', marginBottom: '1rem' }}>{error}</p>}
          <button type="submit" style={{ width: '100%' }}>Entrar</button>
        </form>
      </div>
    </div>
  );
};

export default LenderLogin;
