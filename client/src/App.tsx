import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import NotFound from "@/pages/NotFound";
import { Route, Switch } from "wouter";
import ErrorBoundary from "./components/ErrorBoundary";
import { ThemeProvider } from "./contexts/ThemeContext";
import Home from "./pages/Home";
import Series from "./pages/Series";
import SeriesDetail from "./pages/SeriesDetail";
import Library from "./pages/Library";
import ComicDetail from "./pages/ComicDetail";
import Register from "./pages/Register";
import NewReleases from "./pages/NewReleases";

function Router() {
  return (
    <Switch>
      <Route path={"/"} component={Home} />
      <Route path={"/series"} component={Series} />
      <Route path={"/series/:seriesName"} component={SeriesDetail} />
      <Route path={"/library"} component={Library} />
      <Route path={"/comic/:id"} component={ComicDetail} />
      <Route path={"/register"} component={Register} />
      <Route path={"/new-releases"} component={NewReleases} />
      <Route path={"/404"} component={NotFound} />
      <Route component={NotFound} />
    </Switch>
  );
}

function App() {
  return (
    <ErrorBoundary>
      <ThemeProvider defaultTheme="light">
        <TooltipProvider>
          <Toaster />
          <Router />
        </TooltipProvider>
      </ThemeProvider>
    </ErrorBoundary>
  );
}

export default App;
