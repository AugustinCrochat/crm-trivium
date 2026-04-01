import { BrowserRouter, Routes, Route, NavLink, useLocation } from 'react-router-dom';
import AdminDashboard from './components/AdminDashboard';
import Simulator from './components/Simulator';
import LenderLogin from './components/LenderLogin';
import LenderDashboard from './components/LenderDashboard';
import './index.css';

function Navigation() {
  const location = useLocation();
  const isAdminPath = location.pathname.startsWith('/admin');

  return (
    <header>
      <h1>Sistema de Préstamos</h1>
      <nav>
        {isAdminPath ? (
          <NavLink to="/admin" end className={({ isActive }) => isActive ? 'active' : ''}>
            Panel de Control Admin
          </NavLink>
        ) : (
          <>
            <NavLink to="/" end className={({ isActive }) => isActive ? 'active' : ''}>
              Simulador
            </NavLink>
            <NavLink to="/lender" className={({ isActive }) => isActive ? 'active' : ''}>
              Mi Cuenta
            </NavLink>
          </>
        )}
      </nav>
    </header>
  );
}

function App() {
  return (
    <BrowserRouter>
      <div className="container">
        <Navigation />

        <main>
          <Routes>
            <Route path="/" element={<Simulator />} />
            <Route path="/admin" element={<AdminDashboard />} />
            <Route path="/lender" element={<LenderLogin />} />
            <Route path="/lender/:id" element={<LenderDashboard />} />
          </Routes>
        </main>
        
        <footer style={{ marginTop: '4rem', paddingTop: '2rem', borderTop: '1px solid #eee', textAlign: 'center' }}>
          <NavLink to="/admin" style={{ color: '#ccc', fontSize: '0.8rem', textDecoration: 'none' }}>
            Admin Access
          </NavLink>
        </footer>
      </div>
    </BrowserRouter>
  );
}

export default App;
