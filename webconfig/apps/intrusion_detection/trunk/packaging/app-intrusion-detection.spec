
Name: app-intrusion-detection
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: Intrusion Detection
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-network

%description
The advanced Intrusion Detection system performs security scanning at the network level in real-time.

%package core
Summary: Intrusion Detection - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-network-core
Requires: csplugin-routewatch
Requires: snort >= 2.9.0.4
Requires: snort-gpl-rules

%description core
The advanced Intrusion Detection system performs security scanning at the network level in real-time.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/intrusion_detection
cp -r * %{buildroot}/usr/clearos/apps/intrusion_detection/

install -d -m 0755 %{buildroot}/var/clearos/intrusion_detection
install -D -m 0644 packaging/routewatch-intrusion-detection.conf %{buildroot}/etc/clearsync.d/routewatch-intrusion-detection.conf
install -D -m 0644 packaging/snort.php %{buildroot}/var/clearos/base/daemon/snort.php

%post
logger -p local6.notice -t installer 'app-intrusion-detection - installing'

%post core
logger -p local6.notice -t installer 'app-intrusion-detection-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/intrusion_detection/deploy/install ] && /usr/clearos/apps/intrusion_detection/deploy/install
fi

[ -x /usr/clearos/apps/intrusion_detection/deploy/upgrade ] && /usr/clearos/apps/intrusion_detection/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-intrusion-detection - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-intrusion-detection-core - uninstalling'
    [ -x /usr/clearos/apps/intrusion_detection/deploy/uninstall ] && /usr/clearos/apps/intrusion_detection/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/intrusion_detection/controllers
/usr/clearos/apps/intrusion_detection/htdocs
/usr/clearos/apps/intrusion_detection/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/intrusion_detection/packaging
%exclude /usr/clearos/apps/intrusion_detection/tests
%dir /usr/clearos/apps/intrusion_detection
%dir /var/clearos/intrusion_detection
/usr/clearos/apps/intrusion_detection/deploy
/usr/clearos/apps/intrusion_detection/language
/usr/clearos/apps/intrusion_detection/libraries
/etc/clearsync.d/routewatch-intrusion-detection.conf
/var/clearos/base/daemon/snort.php
