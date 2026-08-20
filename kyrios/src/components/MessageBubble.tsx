import type { Message } from '../data/mockData'

interface MessageBubbleProps {
  message: Message
}

export function MessageBubble({ message }: MessageBubbleProps) {
  if (message.isOwn) {
    return (
      <div className="flex justify-end px-4 mb-3">
        <div className="max-w-[75%] glass rounded-2xl rounded-br-md px-4 py-2.5 bg-indigo-500/30">
          <p className="text-[15px] leading-relaxed">{message.text}</p>
        </div>
      </div>
    )
  }

  return (
    <div className="flex gap-2 px-4 mb-3">
      <img
        src={message.senderAvatar}
        alt={message.senderName}
        className="w-8 h-8 rounded-full object-cover shrink-0 mt-1"
      />
      <div className="max-w-[75%]">
        <div className="glass rounded-2xl rounded-bl-md px-4 py-2.5">
          <p className="text-xs font-semibold text-indigo-300 mb-1">{message.senderName}</p>
          {message.text && (
            <p className="text-[15px] leading-relaxed">{message.text}</p>
          )}
          {message.images && (
            <div className="flex gap-1 mt-2">
              {message.images.map((img, i) => (
                <img
                  key={i}
                  src={img}
                  alt=""
                  className="w-20 h-20 rounded-xl object-cover"
                />
              ))}
            </div>
          )}
        </div>
        {message.reactions && (
          <div className="flex gap-2 mt-1.5 ml-2">
            {message.reactions.map((r, i) => (
              <span
                key={i}
                className="glass text-xs px-2 py-0.5 rounded-full flex items-center gap-1"
              >
                {r.emoji} {String(r.count).padStart(2, '0')}
              </span>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export function TimeDivider({ time }: { time: string }) {
  return (
    <div className="flex justify-center my-4">
      <span className="text-xs text-green-400/80 font-medium">{time}</span>
    </div>
  )
}

interface MessageInputProps {
  onSend?: (text: string) => void
}

export function MessageInput({ onSend }: MessageInputProps) {
  return (
    <div className="px-4 py-3 pb-6">
      <div className="glass rounded-full flex items-center gap-2 px-4 py-2">
        <input
          type="text"
          placeholder="Type here"
          className="flex-1 bg-transparent text-[15px] placeholder:text-white/40 outline-none"
          onKeyDown={(e) => {
            if (e.key === 'Enter' && e.currentTarget.value.trim()) {
              onSend?.(e.currentTarget.value)
              e.currentTarget.value = ''
            }
          }}
        />
        <button className="text-white/50 hover:text-white p-1">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
            <circle cx="12" cy="13" r="4" />
          </svg>
        </button>
        <button className="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white/70 hover:bg-white/20">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
        </button>
      </div>
    </div>
  )
}
