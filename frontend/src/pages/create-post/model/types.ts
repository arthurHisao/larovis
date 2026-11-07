export interface Department {
  id: number;
  name: string;
  slug: string;
}

export interface CreatePostPayload {
  content: string;
  postType: string;
  departmentVisibility: string;
  images: File[];
}
