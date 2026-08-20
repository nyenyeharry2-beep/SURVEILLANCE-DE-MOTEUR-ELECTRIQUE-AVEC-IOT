type AvatarProps = {
  src?: string;
  name: string;
  size?: number;
  online?: boolean;
  initials?: string;
};

export function Avatar({ src, name, size = 48, online, initials }: AvatarProps) {
  const style = { width: size, height: size, fontSize: size * 0.35 };

  return (
    <div className="avatar-wrap" style={{ width: size, height: size }}>
      {src ? (
        <img className="avatar" src={src} alt={name} style={style} />
      ) : (
        <div className="avatar avatar--initials" style={style}>
          {initials ?? name.slice(0, 2).toUpperCase()}
        </div>
      )}
      {online && <span className="avatar__online" />}
    </div>
  );
}
