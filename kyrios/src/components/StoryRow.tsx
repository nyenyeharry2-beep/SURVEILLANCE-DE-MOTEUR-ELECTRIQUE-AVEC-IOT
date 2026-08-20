import { Search, Camera, MoreVertical, Plus } from 'lucide-react'
import type { Story } from '../data/mockData'

interface StoryRowProps {
  stories: Story[]
}

export function StoryRow({ stories }: StoryRowProps) {
  return (
    <div className="flex gap-4 overflow-x-auto scrollbar-hide px-5 py-3">
      {stories.map((story) => (
        <div key={story.id} className="flex flex-col items-center gap-1.5 shrink-0">
          <div className="relative">
            <div
              className={`w-14 h-14 rounded-full p-0.5 ${
                story.isAdd
                  ? 'border-2 border-dashed border-white/30'
                  : story.isLive
                    ? 'bg-gradient-to-tr from-red-500 via-pink-500 to-purple-500'
                    : 'bg-gradient-to-tr from-indigo-500 to-purple-500'
              }`}
            >
              <img
                src={story.avatar}
                alt={story.name}
                className="w-full h-full rounded-full object-cover border-2 border-[#0a0a0f]"
              />
            </div>
            {story.isAdd && (
              <div className="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center border-2 border-[#0a0a0f]">
                <Plus size={12} strokeWidth={3} />
              </div>
            )}
            {story.isLive && (
              <span className="absolute -bottom-1 left-1/2 -translate-x-1/2 text-[9px] font-bold bg-red-500 text-white px-1.5 py-0.5 rounded-full whitespace-nowrap">
                Live
              </span>
            )}
          </div>
          <span className="text-[11px] text-white/60 max-w-[56px] truncate">{story.name}</span>
        </div>
      ))}
    </div>
  )
}

interface FilterPillsProps {
  filters: readonly string[]
  active: string
  onChange: (filter: string) => void
}

export function FilterPills({ filters, active, onChange }: FilterPillsProps) {
  return (
    <div className="flex gap-2 overflow-x-auto scrollbar-hide px-5 pb-3">
      {filters.map((filter) => (
        <button
          key={filter}
          onClick={() => onChange(filter)}
          className={`px-4 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-all ${
            active === filter
              ? 'glass-strong text-white'
              : 'text-white/50 hover:text-white/70'
          }`}
        >
          {filter}
        </button>
      ))}
    </div>
  )
}

export { Search, Camera, MoreVertical }
