
Name: app-antiphishing
Version: 6.2.0.beta3
Release: 1%{dist}
Summary: Gateway Antiphishing
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = %{version}-%{release}
Requires: app-base
Requires: app-antivirus

%description
Gateway Antiphishing provides protection from your network and server.

%package core
Summary: Gateway Antiphishing - APIs and install
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-antivirus-core

%description core
Gateway Antiphishing provides protection from your network and server.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/antiphishing
cp -r * %{buildroot}/usr/clearos/apps/antiphishing/


%post
logger -p local6.notice -t installer 'app-antiphishing - installing'

%post core
logger -p local6.notice -t installer 'app-antiphishing-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/antiphishing/deploy/install ] && /usr/clearos/apps/antiphishing/deploy/install
fi

[ -x /usr/clearos/apps/antiphishing/deploy/upgrade ] && /usr/clearos/apps/antiphishing/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-antiphishing - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-antiphishing-core - uninstalling'
    [ -x /usr/clearos/apps/antiphishing/deploy/uninstall ] && /usr/clearos/apps/antiphishing/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/antiphishing/controllers
/usr/clearos/apps/antiphishing/htdocs
/usr/clearos/apps/antiphishing/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/antiphishing/packaging
%exclude /usr/clearos/apps/antiphishing/tests
%dir /usr/clearos/apps/antiphishing
/usr/clearos/apps/antiphishing/deploy
/usr/clearos/apps/antiphishing/language
/usr/clearos/apps/antiphishing/libraries
