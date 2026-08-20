import { Link } from "react-router-dom";
import { Avatar } from "../components/Avatar";
import { activeFriends, conversations } from "../data/mock";
import "./MessagesScreen.css";

export function MessagesScreen() {
  return (
    <div className="screen messages-screen">
      <header className="screen-header">
        <div className="screen-header__brand">
          <span className="kyrios-logo">K</span>
          <h1>Messages</h1>
        </div>
        <button type="button" className="icon-btn" aria-label="Filter">
          ☰
        </button>
      </header>

      <div className="search-bar">
        <span aria-hidden>🔍</span>
        <input type="search" placeholder="Search..." aria-label="Search conversations" />
      </div>

      <section className="active-friends">
        <div className="section-head">
          <h2>Active friends</h2>
          <Link to="/discover">All ›</Link>
        </div>
        <div className="active-friends__scroll">
          {activeFriends.map((friend) => (
            <div key={friend.id} className="active-friend">
              <Avatar src={friend.avatar} name={friend.name} size={56} online={friend.online} />
              <span>{friend.name}</span>
            </div>
          ))}
        </div>
      </section>

      <section className="recents">
        <div className="section-head">
          <h2>Recents</h2>
          <button type="button" className="icon-btn" aria-label="More">
            ⋯
          </button>
        </div>
        <ul className="conversation-list">
          {conversations.map((convo) => (
            <li key={convo.id}>
              <Link to={`/chat/${convo.id}`} className="conversation-row">
                <Avatar
                  src={convo.user.avatar}
                  name={convo.user.name}
                  initials={convo.user.initials}
                  size={52}
                  online={convo.user.online}
                />
                <div className="conversation-row__body">
                  <div className="conversation-row__top">
                    <span className="conversation-row__name">{convo.user.name}</span>
                    <span className="conversation-row__time">{convo.time}</span>
                  </div>
                  <p className={`conversation-row__preview${convo.typing ? " conversation-row__preview--typing" : ""}`}>
                    {convo.typing ? `${convo.user.name.split(" ")[0]} Typing...` : convo.lastMessage}
                  </p>
                </div>
                {convo.unread ? <span className="badge">{convo.unread}</span> : null}
              </Link>
            </li>
          ))}
        </ul>
      </section>

      <Link to="/chat/c1" className="fab" aria-label="New message">
        +
      </Link>
    </div>
  );
}
