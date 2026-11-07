export interface ChatUser {
  id: number;
  name: string;
  email?: string;
  role?: string;
  avatar?: string | null;
  department?: string | null;
  isOnline?: boolean;
}

export interface ChatMessageReference {
  referId: number;
  text: string;
  senderId?: number;
}

export interface ChatMessage {
  id: number;
  senderId: number;
  text: string;
  time: string;
  refer: ChatMessageReference[];
}

export interface CursorPaginationMeta {
  path?: string;
  per_page?: number;
  next_cursor?: string | null;
  prev_cursor?: string | null;
}

export interface CursorPaginationLinks {
  first?: string | null;
  last?: string | null;
  prev?: string | null;
  next?: string | null;
}

export interface CursorPaginatedResponse<T> {
  data: T[];
  links?: CursorPaginationLinks;
  meta?: CursorPaginationMeta;
}
