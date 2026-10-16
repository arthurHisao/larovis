export interface Author {
  id: number;
  name: string;
  avatar: string;
  department: string;
}

export interface Post {
  id: number;
  author: Author;
  content: string;
  createdAt: string;
  likesCount: number;
  commentsCount: number;
  isLiked?: boolean;
  isSaved?: boolean;
}

export interface Like {
  message: "Liked" | "Unliked";
  isLiked: boolean;
}
