import { useState } from "react";
import { insights } from "../data/mock";
import "./InsightsScreen.css";

type GenderFilter = "all" | "female" | "male" | "nonbinary";

const genderTabs: { id: GenderFilter; label: string }[] = [
  { id: "all", label: "All" },
  { id: "female", label: "Female" },
  { id: "male", label: "Male" },
  { id: "nonbinary", label: "Non-binary" },
];

export function InsightsScreen() {
  const [gender, setGender] = useState<GenderFilter>("all");

  return (
    <div className="screen insights-screen">
      <header className="screen-header insights-screen__header">
        <button type="button" className="icon-btn" aria-label="Menu">
          ☰
        </button>
        <div>
          <h1>Insights</h1>
          <p className="insights-screen__updated">Updated {insights.updatedHoursAgo} hours ago</p>
        </div>
      </header>

      <div className="insight-card insight-card--hero">
        <span className="insight-card__label">Total members</span>
        <span className="insight-card__value">{insights.totalMembers.toLocaleString()}</span>
        <span className="insight-card__delta">+ {insights.growth7d} new members over the last 7 days</span>
      </div>

      <section className="insight-section">
        <h2>Top locations</h2>
        <ul className="location-bars">
          {insights.locations.map((loc) => (
            <li key={loc.city}>
              <div className="location-bars__label">
                <span>{loc.city}</span>
                <span>{loc.pct}%</span>
              </div>
              <div className="location-bars__track">
                <div className="location-bars__fill" style={{ width: `${loc.pct * 100}%` }} />
              </div>
            </li>
          ))}
        </ul>
      </section>

      <section className="insight-section">
        <h2>Age</h2>
        <div className="gender-tabs">
          {genderTabs.map((tab) => (
            <button
              key={tab.id}
              type="button"
              className={`gender-tabs__btn${gender === tab.id ? " gender-tabs__btn--active" : ""}`}
              onClick={() => setGender(tab.id)}
            >
              {tab.label}
            </button>
          ))}
        </div>
        <ul className="age-bars">
          {insights.ageGroups.map((group) => (
            <li key={group.range}>
              <span className="age-bars__range">{group.range}</span>
              <div className="age-bars__track">
                <div className="age-bars__fill" style={{ height: `${group.pct}%` }} />
              </div>
              <span className="age-bars__pct">{group.pct}%</span>
            </li>
          ))}
        </ul>
      </section>
    </div>
  );
}
