const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3001';

export const API = {
  config: `${API_BASE_URL}/api/config`,
  lenders: `${API_BASE_URL}/api/lenders`,
  loans: `${API_BASE_URL}/api/loans`,
  payments: `${API_BASE_URL}/api/payments`,
  
  lenderLogin: (code: string) => `${API_BASE_URL}/api/lender/login/${code}`,
  lenderDashboard: (id: number) => `${API_BASE_URL}/api/lender/${id}/dashboard`,
  loanPayments: (id: number) => `${API_BASE_URL}/api/loans/${id}/payments`,
  togglePayment: (id: number) => `${API_BASE_URL}/api/payments/${id}/toggle`,
};
