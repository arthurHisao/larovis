export interface ChatUser {
  id: number;
  key?: string; // "user-5" ou "group-12"
  chatId?: number | null;
  isGroup?: boolean;
  name: string;
  email?: string | null;
  avatar?: string | null;
  department?: string | null;
  role?: string | null;
  membersCount?: number | null;
  isOnline?: boolean;
  coverImage?: string | null;
  creatorId?: number | null;
  isAdmin?: boolean;
  isCreator?: boolean;
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

export interface CreateGroupPayload {
  name: string;
  memberIds: number[];
}

export interface UpdateGroupPayload {
  id: number;
  name: string;
  memberIds: number[];
  coverImageFile?: File | null;
  removeCoverImage?: boolean;
}

export interface SendMessagePayload {
  receiverId?: number;
  chatId?: number;
  text: string;
  parentId?: number;
}
