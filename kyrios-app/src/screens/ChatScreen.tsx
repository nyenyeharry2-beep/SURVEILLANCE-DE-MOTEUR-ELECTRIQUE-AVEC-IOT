import { Link, useParams } from "react-router-dom";
import { Avatar } from "../components/Avatar";
import { getConversation, getMessages } from "../data/mock";
import "./ChatScreen.css";

export function ChatScreen() {
  const { id = "c1" } = useParams();
  const conversation = getConversation(id);
  const messages = getMessages(id);

  if (!conversation) {
    return (
      <div className="screen">
        <p>Conversation not found.</p>
        <Link to="/">Back to Messages</Link>
      </div>
    );
  }

  const { user } = conversation;

  return (
    <div className="screen chat-screen">
      <header className="chat-header">
        <Link to="/" className="back-btn" aria-label="Back">
          ←
        </Link>
        <Avatar src={user.avatar} name={user.name} initials={user.initials} size={40} online={user.online} />
        <div className="chat-header__info">
          <span className="chat-header__name">{user.name}</span>
          {user.online && <span className="chat-header__status">Online</span>}
        </div>
        <button type="button" className="icon-btn" aria-label="Menu">
          ⋮
        </button>
      </header>

      <div className="chat-messages">
        {messages.map((msg) => (
          <div key={msg.id} className={`chat-bubble-row${msg.outgoing ? " chat-bubble-row--out" : ""}`}>
            {!msg.outgoing && (
              <Avatar src={user.avatar} name={user.name} size={28} />
            )}
            <div className={`chat-bubble chat-bubble--${msg.type ?? "text"}${msg.outgoing ? " chat-bubble--out" : ""}`}>
              {msg.type === "image" && msg.imageUrl && (
                <img src={msg.imageUrl} alt="" className="chat-bubble__image" />
              )}
              {msg.type === "voice" && (
                <div className="voice-message">
                  <button type="button" className="voice-message__play" aria-label="Play">
                    ▶
                  </button>
                  <div className="voice-message__wave" aria-hidden />
                  <span className="voice-message__duration">{msg.voiceDuration ?? "0:43"}</span>
                </div>
              )}
              {(!msg.type || msg.type === "text") && msg.text && <p>{msg.text}</p>}
              {msg.reactions && msg.reactions.length > 0 && (
                <div className="chat-reactions">
                  {msg.reactions.map((r) => (
                    <span key={r.emoji}>
                      {r.emoji} {String(r.count).padStart(2, "0")}
                    </span>
                  ))}
                </div>
              )}
            </div>
            {msg.outgoing && <span className="read-receipt" aria-label="Read">✓✓</span>}
          </div>
        ))}
        {conversation.typing && (
          <p className="typing-indicator">{user.name.split(" ")[0]} Typing...</p>
        )}
      </div>

      <footer className="chat-composer">
        <button type="button" className="icon-btn" aria-label="Attach">
          📎
        </button>
        <input type="text" placeholder="Type message..." aria-label="Message" />
        <button type="button" className="icon-btn" aria-label="Emoji">
          😊
        </button>
      </footer>
    </div>
  );
}
