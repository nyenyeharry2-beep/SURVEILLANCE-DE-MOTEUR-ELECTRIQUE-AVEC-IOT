import { Avatar } from "../components/Avatar";
import { activeFriends, currentUser, feedPosts } from "../data/mock";
import "./DiscoverScreen.css";

export function DiscoverScreen() {
  return (
    <div className="screen discover-screen">
      <header className="discover-header">
        <div className="discover-header__actions">
          <button type="button" className="notif-btn" aria-label="Notifications">
            🔔
            <span className="notif-btn__badge">3</span>
          </button>
          <button type="button" className="icon-btn" aria-label="Messages">
            💬
          </button>
          <Avatar src={currentUser.avatar} name={currentUser.name} size={36} />
        </div>
        <nav className="discover-tabs">
          <button type="button" className="discover-tabs__btn discover-tabs__btn--active">
            Discover
          </button>
          <button type="button" className="discover-tabs__btn">
            Following
          </button>
        </nav>
      </header>

      <section className="stories-row">
        <div className="story story--yours">
          <div className="story__ring story__ring--add">
            <span>+</span>
          </div>
          <span>Your Story</span>
        </div>
        {activeFriends.slice(0, 4).map((friend) => (
          <div key={friend.id} className="story">
            <img src={friend.avatar} alt={friend.name} className="story__ring" />
            <span>{friend.name.split(" ")[0]}</span>
          </div>
        ))}
      </section>

      <section className="feed">
        <h2 className="feed__heading">Recently Post</h2>
        {feedPosts.map((post) => (
          <article key={post.id} className="post-card">
            <div className="post-card__head">
              <Avatar src={post.author.avatar} name={post.author.name} size={40} />
              <div>
                <strong>{post.author.name}</strong>
                <span className="post-card__meta">
                  {post.community ? `Posted in ${post.community}` : "Posted"} · {post.time}
                </span>
              </div>
              <button type="button" className="icon-btn" aria-label="More">
                ⋯
              </button>
            </div>
            <p className="post-card__text">
              {post.text.split("@").map((part, i) =>
                i === 0 ? (
                  part
                ) : (
                  <span key={i}>
                    <span className="mention">@{part.split(" ")[0]}</span>
                    {part.slice(part.indexOf(" "))}
                  </span>
                ),
              )}
            </p>
            {post.images && post.images.length > 0 && (
              <div className={`post-card__media post-card__media--${post.images.length}`}>
                {post.images.map((img) => (
                  <img key={img} src={img} alt="" />
                ))}
              </div>
            )}
            <div className="post-card__stats">
              <span>♥ {(post.likes / 1000).toFixed(1)}k</span>
              <span>💬 {(post.comments / 1000).toFixed(1)}k</span>
              <span>🔖 99</span>
            </div>
          </article>
        ))}
      </section>

      <button type="button" className="discover-fab" aria-label="Create post">
        +
      </button>
    </div>
  );
}
