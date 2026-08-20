import { Link } from "react-router-dom";
import { communities } from "../data/mock";
import "./CommunitiesScreen.css";

export function CommunitiesScreen() {
  return (
    <div className="screen communities-screen">
      <header className="screen-header">
        <h1>Communities</h1>
        <button type="button" className="icon-btn communities-screen__add" aria-label="Create community">
          +
        </button>
      </header>

      <div className="search-bar">
        <span aria-hidden>🔍</span>
        <input type="search" placeholder="Search communities..." aria-label="Search communities" />
      </div>

      <ul className="community-list">
        {communities.map((community) => (
          <li key={community.id}>
            <Link to={`/chat/c5`} className="community-row">
              <span className="community-row__dot" style={{ background: community.color }} />
              <div className="community-row__body">
                <span className="community-row__name">{community.name}</span>
                <span className="community-row__members">{community.members.toLocaleString()} members</span>
              </div>
              <span className="community-row__chevron" aria-hidden>
                ›
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
