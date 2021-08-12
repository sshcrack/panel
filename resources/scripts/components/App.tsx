import React, { useEffect } from 'react';
import ReactGA from 'react-ga';
import { hot } from 'react-hot-loader/root';
import { Route, Router, Switch, useLocation } from 'react-router-dom';
import { StoreProvider } from 'easy-peasy';
import { store } from '@/state';
import DashboardRouter from '@/routers/DashboardRouter';
import ServerRouter from '@/routers/ServerRouter';
import AuthenticationRouter from '@/routers/AuthenticationRouter';
import { SiteSettings } from '@/state/settings';
import ProgressBar from '@/components/elements/ProgressBar';
import { NotFound } from '@/components/elements/ScreenBlock';
import tw, { GlobalStyles as TailwindGlobalStyles } from 'twin.macro';
import GlobalStylesheet from '@/assets/css/GlobalStylesheet';
import { history } from '@/components/history';
import { setupInterceptors } from '@/api/interceptors';

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
    KriegerhostUser?: {
        uuid: string;
        username: string;
        email: string;
        /* eslint-disable camelcase */
        root_admin: boolean;
        use_totp: boolean;
        language: string;
        updated_at: string;
        created_at: string;
        /* eslint-enable camelcase */
    };
}

setupInterceptors(history);

const Pageview = () => {
    const { pathname } = useLocation();

    useEffect(() => {
        ReactGA.pageview(pathname);
    }, [ pathname ]);

    return null;
};

const App = () => {
    const { KriegerhostUser, SiteConfiguration } = (window as ExtendedWindow);
    if (KriegerhostUser && !store.getState().user.data) {
        store.getActions().user.setUserData({
            uuid: KriegerhostUser.uuid,
            username: KriegerhostUser.username,
            email: KriegerhostUser.email,
            language: KriegerhostUser.language,
            rootAdmin: KriegerhostUser.root_admin,
            useTotp: KriegerhostUser.use_totp,
            createdAt: new Date(KriegerhostUser.created_at),
            updatedAt: new Date(KriegerhostUser.updated_at),
        });
    }

    if (!store.getState().settings.data) {
        store.getActions().settings.setSettings(SiteConfiguration!);
    }

    useEffect(() => {
        if (SiteConfiguration?.analytics) {
            ReactGA.initialize(SiteConfiguration!.analytics);
        }
    }, []);

    return (
        <>
            <GlobalStylesheet/>
            <TailwindGlobalStyles/>
            <StoreProvider store={store}>
                <ProgressBar/>
                <div css={tw`mx-auto w-auto`}>
                    <Router history={history}>
                        {SiteConfiguration?.analytics && <Pageview/>}
                        <Switch>
                            <Route path="/server/:id" component={ServerRouter}/>
                            <Route path="/auth" component={AuthenticationRouter}/>
                            <Route path="/" component={DashboardRouter}/>
                            <Route path={'*'} component={NotFound}/>
                        </Switch>
                    </Router>
                </div>
            </StoreProvider>
        </>
    );
};

export default hot(App);
