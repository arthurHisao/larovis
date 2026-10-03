export interface CommentAuthor {
  id: number;
  name: string;
  avatar?: string;
}

export interface Comment {
  id: number;
  postId: number;
  mention: string;
  author: CommentAuthor;
  content: string;
  createdAt: string;
  parentId?: number;
  replies?: Comment[];
}
