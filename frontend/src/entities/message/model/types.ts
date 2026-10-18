export interface ChatUser {
  id: number;
  name: string;
  email?: string;
  role?: string;
  avatar?: string | null;
}

export interface ChatMessageReference {
  referId: number;
  text: string;
}

export interface ChatMessage {
  id: number;
  senderId: number;
  text: string;
  time: string;
  refer: ChatMessageReference[];
}
