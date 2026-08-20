import { Bell, MessageCircle, Search, Heart, MessageSquare, Bookmark } from 'lucide-react'
import { discoverPosts, stories } from '../data/mockData'

export function DiscoverPage() {
  return (
    <div className="flex flex-col h-full overflow-y-auto scrollbar-hide pb-28">
      <header className="flex items-center justify-between px-5 pt-2 pb-4">
        <div className="flex items-center gap-3">
          <button className="relative p-2 text-white/60 hover:text-white">
            <Bell size={22} />
            <span className="absolute top-1 right-1 w-4 h-4 bg-red-500 rounded-full text-[10px] font-bold flex items-center justify-center">
              3
            </span>
          </button>
          <button className="p-2 text-white/60 hover:text-white">
            <MessageCircle size={22} />
          </button>
        </div>
        <img
          src="https://i.pravatar.cc/150?u=me"
          alt="Profile"
          className="w-9 h-9 rounded-full object-cover"
        />
      </header>

      <div className="px-5 mb-4">
        <div className="flex gap-6 border-b border-white/10">
          <button className="pb-2 text-sm font-bold border-b-2 border-indigo-400">Discover</button>
          <button className="pb-2 text-sm text-white/40">Following</button>
        </div>
      </div>

      {/* Stories */}
      <div className="flex gap-3 overflow-x-auto scrollbar-hide px-5 pb-4">
        {stories.slice(0, 5).map((story) => (
          <div key={story.id} className="shrink-0">
            <div className="w-16 h-20 rounded-2xl overflow-hidden border border-white/10">
              <img src={story.avatar} alt={story.name} className="w-full h-full object-cover" />
            </div>
            <p className="text-[10px] text-white/50 mt-1 text-center truncate w-16">
              {story.isAdd ? 'Your Story' : story.name}
            </p>
          </div>
        ))}
      </div>

      <div className="px-5">
        <h2 className="text-lg font-bold mb-3">Recently Post</h2>
        {discoverPosts.map((post) => (
          <article key={post.id} className="glass rounded-2xl mb-4 overflow-hidden">
            <div className="flex items-center gap-3 p-4">
              <img src={post.avatar} alt={post.author} className="w-10 h-10 rounded-full object-cover" />
              <div className="flex-1">
                <p className="font-semibold text-sm">{post.author}</p>
                <p className="text-xs text-white/40">Posted in u8s · {post.time}</p>
              </div>
              <button className="text-white/40">•••</button>
            </div>
            <p className="px-4 pb-3 text-sm leading-relaxed">
              {post.caption.split('@').map((part, i) =>
                i === 0 ? part : (
                  <span key={i}>
                    <span className="text-indigo-400">@{part.split(' ')[0]}</span>
                    {part.slice(part.indexOf(' '))}
                  </span>
                )
              )}
            </p>
            <img src={post.image} alt="" className="w-full h-48 object-cover" />
            <div className="flex items-center gap-6 p-4 text-sm text-white/60">
              <button className="flex items-center gap-1.5 hover:text-red-400 transition-colors">
                <Heart size={18} /> {(post.likes / 1000).toFixed(1)}k
              </button>
              <button className="flex items-center gap-1.5 hover:text-indigo-400 transition-colors">
                <MessageSquare size={18} /> {post.comments}
              </button>
              <button className="ml-auto hover:text-white transition-colors">
                <Bookmark size={18} />
              </button>
            </div>
          </article>
        ))}
      </div>
    </div>
  )
}

export function CommunitiesPage() {
  return (
    <div className="flex flex-col h-full overflow-y-auto scrollbar-hide pb-28">
      <header className="flex items-center justify-between px-5 pt-2 pb-4">
        <h1 className="text-2xl font-bold">Communities</h1>
        <button className="w-9 h-9 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">
          +
        </button>
      </header>

      <div className="px-5 mb-4">
        <div className="glass rounded-xl flex items-center gap-2 px-4 py-2.5">
          <Search size={18} className="text-white/40" />
          <input
            type="text"
            placeholder="Search communities..."
            className="flex-1 bg-transparent text-sm placeholder:text-white/40 outline-none"
          />
        </div>
      </div>

      <div className="px-5 space-y-2">
        {[
          { name: 'Foodies', icon: '🍕', members: 12400, color: '#f97316' },
          { name: 'Daily Inspiration', icon: '✨', members: 8900, color: '#a855f7' },
          { name: 'Football', icon: '⚽', members: 23100, color: '#22c55e' },
          { name: 'Sneakerheads', icon: '👟', members: 5600, color: '#3b82f6' },
        ].map((c) => (
          <button
            key={c.name}
            className="w-full glass rounded-2xl flex items-center gap-4 p-4 hover:bg-white/10 transition-colors text-left"
          >
            <div
              className="w-12 h-12 rounded-full flex items-center justify-center text-2xl"
              style={{ backgroundColor: `${c.color}20` }}
            >
              {c.icon}
            </div>
            <div>
              <p className="font-semibold">{c.name}</p>
              <p className="text-sm text-white/40">{c.members.toLocaleString()} members</p>
            </div>
          </button>
        ))}
      </div>
    </div>
  )
}
