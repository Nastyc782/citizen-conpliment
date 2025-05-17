export interface User {
  id: number;
  name: string;
  email: string;
  role: 'citizen' | 'admin';
}

export interface Ticket {
  id: number;
  subject: string;
  message: string;
  category: string;
  status: 'submitted' | 'in_progress' | 'resolved';
  priority: 'low' | 'medium' | 'high';
  userId: number;
  agencyId: number;
  createdAt: string;
  updatedAt: string;
  citizen?: User;
  agency?: Agency;
  responses?: Response[];
}

export interface Agency {
  id: number;
  name: string;
  category: string;
  description?: string;
}

export interface Response {
  id: number;
  message: string;
  isInternal: boolean;
  ticketId: number;
  userId: number;
  createdAt: string;
  responder?: User;
}

export interface AuthState {
  user: User | null;
  loading: boolean;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData extends LoginCredentials {
  name: string;
  role?: 'citizen' | 'admin';
}

export interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (credentials: LoginCredentials) => Promise<User>;
  register: (data: RegisterData) => Promise<User>;
  logout: () => void;
} 