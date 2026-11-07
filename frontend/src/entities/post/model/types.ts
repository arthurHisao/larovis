export interface Author {
  id: number;
  name: string;
  avatar: string;
  department: string;
}

export interface PostImage {
  id: number;
  url: string;
}

export interface Post {
  id: number;
  author: Author;
  content: string;
  createdAt: string;
  department?: string;
  department_id?: number | null;
  type?: string;
  images?: PostImage[];
  likesCount: number;
  commentsCount: number;
  isLiked?: boolean;
  isSaved?: boolean;
}

export interface Like {
  message: "Liked" | "Unliked";
  isLiked: boolean;
}
