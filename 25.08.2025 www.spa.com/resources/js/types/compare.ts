export interface CompareMaster {
  id: number
  name: string
  photo: string
  specialization: string
  pricePerHour: number
  rating: number
  reviewsCount?: number
  experience?: number
  location?: string
  services?: string[]
}

export interface CompareProps {
  compareList: CompareMaster[]
  counts?: {
    bookings: number
    favorites: number
    reviews: number
  }
  userStats?: {
    totalSpent: number
    totalBookings: number
    memberSince: string
  }
}