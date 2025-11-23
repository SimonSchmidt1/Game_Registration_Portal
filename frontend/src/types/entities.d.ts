/** Shared entity type hints (optional; not imported yet). */

export interface TeamMember {
  id: number
  name: string
  role: 'scrum_master' | 'member'
}

export interface TeamEntity {
  id: number
  name: string
  invite_code: string
  academic_year?: { id: number; name: string } | null
  is_scrum_master: boolean
  members: TeamMember[]
}

export interface GameEntity {
  id: number
  title: string
  description?: string | null
  category?: string | null
  rating?: number | string
  views?: number
  release_date?: string | null
  team?: { id: number; name: string } | null
  academic_year?: { id: number; name: string } | null
  trailer_path?: string | null
  trailer_url?: string | null
  splash_screen_path?: string | null
  export_path?: string | null
  source_code_path?: string | null
}

export type ProjectType = 'game' | 'application' | 'library' | 'other'
