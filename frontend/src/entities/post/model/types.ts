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
