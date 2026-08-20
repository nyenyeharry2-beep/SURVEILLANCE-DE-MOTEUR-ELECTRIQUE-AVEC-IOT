import { Link } from "react-router-dom";
import { Avatar } from "../components/Avatar";
import { profileUser } from "../data/mock";
import "./ProfileScreen.css";

export function ProfileScreen() {
  return (
    <div className="screen profile-screen">
      <header className="profile-screen__top">
        <Link to="/" className="back-btn" aria-label="Back">
          ←
        </Link>
        <h1>Profile</h1>
        <span className="profile-screen__spacer" />
      </header>

      <div className="profile-hero">
        <Avatar src={profileUser.avatar} name={profileUser.name} size={88} />
        <h2>
          {profileUser.name} <span aria-hidden>👋</span>
        </h2>
        <button type="button" className="btn-follow">
          + Follow
        </button>
      </div>

      <div className="profile-stats">
        <div>
          <strong>{profileUser.posts}</strong>
          <span>Photos</span>
        </div>
        <div>
          <strong>{profileUser.followers}</strong>
          <span>Followers</span>
        </div>
        <div>
          <strong>{profileUser.following}</strong>
          <span>Following</span>
        </div>
      </div>

      <section className="profile-photos">
        <div className="section-head">
          <h2>Photos</h2>
          <Link to="/discover">View all ›</Link>
        </div>
        <div className="photo-grid">
          {profileUser.photos.map((photo, i) => (
            <img key={photo} src={photo} alt="" className={i % 3 === 0 ? "photo-grid__tall" : ""} />
          ))}
        </div>
      </section>

      <Link to="/chat/c1" className="profile-fab" aria-label="Message">
        💬
      </Link>
    </div>
  );
}
