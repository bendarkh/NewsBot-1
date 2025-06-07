import React, { useState } from 'react';
import { Calendar, Globe, Settings, TrendingUp, Clock, FileText, Rss, Zap, Users, BarChart3, Plus, Eye, Edit, Trash2, CheckCircle, AlertCircle, PlayCircle, X, Search, RefreshCw, ArrowRight, Target, MousePointer, Timer, ArrowUp, ArrowDown, Minus } from 'lucide-react';

interface NewsItem {
  id: string;
  title: string;
  source: string;
  publishedAt: string;
  category: string;
  status: 'pending' | 'analyzed' | 'scheduled' | 'published';
  summary: string;
  scheduledTime?: string;
}

interface ScheduledPost {
  id: string;
  title: string;
  content: string;
  scheduledTime: string;
  status: 'scheduled' | 'published' | 'failed';
  category: string;
}

interface TrendTopic {
  id: string;
  title: string;
  searchVolume: string;
  trend: 'up' | 'down' | 'stable';
  category: string;
  relatedQueries: string[];
}

interface KeywordRanking {
  id: string;
  keyword: string;
  currentPosition: number;
  previousPosition: number;
  searchVolume: string;
  difficulty: 'easy' | 'medium' | 'hard';
  url: string;
}

interface WebsiteAnalytics {
  dailyVisitors: number;
  avgSessionDuration: string;
  bounceRate: string;
  topKeywords: Array<{
    keyword: string;
    visitors: number;
    percentage: number;
  }>;
  topPages: Array<{
    page: string;
    visitors: number;
    avgDuration: string;
  }>;
  trafficSources: Array<{
    source: string;
    visitors: number;
    percentage: number;
  }>;
}

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [showAnalysisModal, setShowAnalysisModal] = useState(false);
  const [isAnalyzing, setIsAnalyzing] = useState(false);
  const [analysisProgress, setAnalysisProgress] = useState(0);
  const [showTrends, setShowTrends] = useState(false);
  const [selectedTrend, setSelectedTrend] = useState<TrendTopic | null>(null);
  
  const [newsItems, setNewsItems] = useState<NewsItem[]>([
    {
      id: '1',
      title: 'Yapay Zeka Teknolojisinde Yeni Gelişmeler',
      source: 'TechCrunch',
      publishedAt: '2025-01-27 10:30',
      category: 'AI',
      status: 'analyzed',
      summary: 'OpenAI\'nin yeni GPT modelinde önemli iyileştirmeler...',
      scheduledTime: '2025-01-27 18:00'
    },
    {
      id: '2',
      title: 'Blockchain Teknolojisi ve Web3\'ün Geleceği',
      source: 'Wired',
      publishedAt: '2025-01-27 09:15',
      category: 'Blockchain',
      status: 'pending',
      summary: 'Merkezi olmayan internet teknolojilerinin yükselişi...'
    },
    {
      id: '3',
      title: 'Apple\'ın Yeni M4 Çipinin Performans Testleri',
      source: 'The Verge',
      publishedAt: '2025-01-27 08:45',
      category: 'Hardware',
      status: 'scheduled',
      summary: 'M4 çipinin benchmark sonuçları beklentileri aştı...',
      scheduledTime: '2025-01-27 20:00'
    },
    {
      id: '4',
      title: 'Meta\'nın Yeni VR Teknolojisi Gerçeklik Algısını Değiştiriyor',
      source: 'TechCrunch',
      publishedAt: '2025-01-27 07:30',
      category: 'VR/AR',
      status: 'analyzed',
      summary: 'Meta\'nın son VR teknolojisi ile sanal gerçeklik deneyimi bambaşka bir boyuta taşınıyor...'
    },
    {
      id: '5',
      title: 'Quantum Bilgisayarlar: IBM\'in Yeni Atılımı',
      source: 'Wired',
      publishedAt: '2025-01-27 06:15',
      category: 'Quantum',
      status: 'pending',
      summary: 'IBM\'in yeni quantum işlemcisi geleneksel bilgisayarları geride bırakıyor...'
    },
    {
      id: '6',
      title: 'Tesla\'nın Otonom Sürüş Teknolojisinde Yeni Milestone',
      source: 'The Verge',
      publishedAt: '2025-01-27 05:45',
      category: 'Automotive',
      status: 'analyzed',
      summary: 'Tesla\'nın FSD teknolojisi şehir içi sürüşte %95 başarı oranına ulaştı...'
    }
  ]);

  const [trendTopics] = useState<TrendTopic[]>([
    {
      id: '1',
      title: 'ChatGPT Türkiye',
      searchVolume: '500K+',
      trend: 'up',
      category: 'AI',
      relatedQueries: ['ChatGPT nasıl kullanılır', 'ChatGPT Türkçe', 'AI chatbot']
    },
    {
      id: '2',
      title: 'iPhone 16 Pro',
      searchVolume: '200K+',
      trend: 'up',
      category: 'Mobile',
      relatedQueries: ['iPhone 16 Pro özellikleri', 'iPhone 16 Pro fiyat', 'Apple yeni telefon']
    },
    {
      id: '3',
      title: 'Elektrikli Araba',
      searchVolume: '150K+',
      trend: 'stable',
      category: 'Automotive',
      relatedQueries: ['Tesla Model Y', 'elektrikli araba fiyatları', 'EV şarj istasyonları']
    },
    {
      id: '4',
      title: 'Kripto Para',
      searchVolume: '300K+',
      trend: 'down',
      category: 'Blockchain',
      relatedQueries: ['Bitcoin fiyat', 'Ethereum analiz', 'kripto para borsası']
    },
    {
      id: '5',
      title: 'Metaverse Türkiye',
      searchVolume: '80K+',
      trend: 'up',
      category: 'VR/AR',
      relatedQueries: ['VR gözlük', 'sanal gerçeklik oyunları', 'metaverse nedir']
    },
    {
      id: '6',
      title: '5G Teknolojisi',
      searchVolume: '120K+',
      trend: 'stable',
      category: 'Network',
      relatedQueries: ['5G hız testi', '5G kapsama alanı', '5G telefon modelleri']
    }
  ]);

  const [keywordRankings] = useState<KeywordRanking[]>([
    {
      id: '1',
      keyword: 'yapay zeka haberleri',
      currentPosition: 3,
      previousPosition: 5,
      searchVolume: '12K',
      difficulty: 'medium',
      url: '/ai-news'
    },
    {
      id: '2',
      keyword: 'teknoloji blog',
      currentPosition: 7,
      previousPosition: 8,
      searchVolume: '8.5K',
      difficulty: 'hard',
      url: '/tech-blog'
    },
    {
      id: '3',
      keyword: 'blockchain nedir',
      currentPosition: 12,
      previousPosition: 9,
      searchVolume: '15K',
      difficulty: 'easy',
      url: '/blockchain-guide'
    },
    {
      id: '4',
      keyword: 'iphone 16 inceleme',
      currentPosition: 4,
      previousPosition: 4,
      searchVolume: '25K',
      difficulty: 'medium',
      url: '/iphone-16-review'
    },
    {
      id: '5',
      keyword: 'tesla haberleri',
      currentPosition: 6,
      previousPosition: 10,
      searchVolume: '18K',
      difficulty: 'medium',
      url: '/tesla-news'
    }
  ]);

  const [websiteAnalytics] = useState<WebsiteAnalytics>({
    dailyVisitors: 2847,
    avgSessionDuration: '3:42',
    bounceRate: '42%',
    topKeywords: [
      { keyword: 'yapay zeka', visitors: 1250, percentage: 44 },
      { keyword: 'teknoloji haberleri', visitors: 890, percentage: 31 },
      { keyword: 'blockchain', visitors: 420, percentage: 15 },
      { keyword: 'iphone 16', visitors: 287, percentage: 10 }
    ],
    topPages: [
      { page: '/ai-news', visitors: 1580, avgDuration: '4:15' },
      { page: '/tech-trends', visitors: 920, avgDuration: '3:28' },
      { page: '/blockchain-guide', visitors: 650, avgDuration: '5:12' },
      { page: '/mobile-reviews', visitors: 480, avgDuration: '2:45' }
    ],
    trafficSources: [
      { source: 'Google Organik', visitors: 1820, percentage: 64 },
      { source: 'Sosyal Medya', visitors: 570, percentage: 20 },
      { source: 'Direkt Trafik', visitors: 285, percentage: 10 },
      { source: 'Referans', visitors: 172, percentage: 6 }
    ]
  });

  const [scheduledPosts] = useState<ScheduledPost[]>([
    {
      id: '1',
      title: 'AI Teknolojilerinin İş Dünyasına Etkisi',
      content: 'Yapay zeka teknolojileri, modern iş dünyasında devrim yaratmaya devam ediyor...',
      scheduledTime: '2025-01-27 18:00',
      status: 'scheduled',
      category: 'AI'
    },
    {
      id: '2',
      title: 'Gelecek Nesil Mobil Teknolojiler',
      content: '5G ve 6G teknolojilerinin mobil deneyimi nasıl şekillendirecek...',
      scheduledTime: '2025-01-28 10:00',
      status: 'scheduled',
      category: 'Mobile'
    }
  ]);

  const startAnalysis = async () => {
    setIsAnalyzing(true);
    setAnalysisProgress(0);
    setShowTrends(false);
    
    // Simulated analysis process
    const steps = [
      'Google Trends verilerini alıyor...',
      'Trend konularını analiz ediyor...',
      'İçerik fırsatlarını değerlendiriyor...',
      'Anahtar kelime araştırması yapıyor...',
      'Rekabet analizi gerçekleştiriyor...',
      'İçerik önerileri hazırlıyor...'
    ];

    for (let i = 0; i < steps.length; i++) {
      await new Promise(resolve => setTimeout(resolve, 1000));
      setAnalysisProgress(((i + 1) / steps.length) * 100);
    }

    setIsAnalyzing(false);
    setShowTrends(true);
    setAnalysisProgress(0);
  };

  const generateContentFromTrend = (trend: TrendTopic) => {
    setSelectedTrend(trend);
    
    // Simulate content generation
    const newNewsItem = {
      id: Date.now().toString(),
      title: `${trend.title}: Türkiye'deki Son Gelişmeler ve Trend Analizi`,
      source: 'Google Trends',
      publishedAt: new Date().toLocaleString('tr-TR'),
      category: trend.category,
      status: 'analyzed' as const,
      summary: `${trend.title} konusunda Türkiye'de artan ilgi ve son gelişmeler detaylı olarak incelendi...`
    };

    setNewsItems(prev => [newNewsItem, ...prev]);
    setShowAnalysisModal(false);
    setShowTrends(false);
    setSelectedTrend(null);
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'text-yellow-400';
      case 'analyzed': return 'text-blue-400';
      case 'scheduled': return 'text-purple-400';
      case 'published': return 'text-green-400';
      case 'failed': return 'text-red-400';
      default: return 'text-gray-400';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'pending': return <Clock className="w-4 h-4" />;
      case 'analyzed': return <CheckCircle className="w-4 h-4" />;
      case 'scheduled': return <Calendar className="w-4 h-4" />;
      case 'published': return <Globe className="w-4 h-4" />;
      case 'failed': return <AlertCircle className="w-4 h-4" />;
      default: return <Clock className="w-4 h-4" />;
    }
  };

  const getTrendIcon = (trend: string) => {
    switch (trend) {
      case 'up': return <TrendingUp className="w-4 h-4 text-green-400" />;
      case 'down': return <TrendingUp className="w-4 h-4 text-red-400 rotate-180" />;
      default: return <div className="w-4 h-4 bg-gray-400 rounded-full" />;
    }
  };

  const getRankingChange = (current: number, previous: number) => {
    if (current < previous) {
      return { icon: <ArrowUp className="w-3 h-3 text-green-400" />, color: 'text-green-400', change: previous - current };
    } else if (current > previous) {
      return { icon: <ArrowDown className="w-3 h-3 text-red-400" />, color: 'text-red-400', change: current - previous };
    } else {
      return { icon: <Minus className="w-3 h-3 text-gray-400" />, color: 'text-gray-400', change: 0 };
    }
  };

  const getDifficultyColor = (difficulty: string) => {
    switch (difficulty) {
      case 'easy': return 'bg-green-500/20 text-green-400';
      case 'medium': return 'bg-yellow-500/20 text-yellow-400';
      case 'hard': return 'bg-red-500/20 text-red-400';
      default: return 'bg-gray-500/20 text-gray-400';
    }
  };

  const AnalysisModal = () => (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
      <div className="bg-slate-900/95 backdrop-blur-sm border border-white/20 rounded-2xl p-8 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-xl font-semibold text-white">
            {showTrends ? 'Google Trends - Türkiye Güncel Konuları' : 'Trend Analizi'}
          </h3>
          {!isAnalyzing && (
            <button 
              onClick={() => {
                setShowAnalysisModal(false);
                setShowTrends(false);
                setSelectedTrend(null);
              }}
              className="p-2 hover:bg-white/10 rounded-lg transition-colors"
            >
              <X className="w-5 h-5 text-gray-400" />
            </button>
          )}
        </div>

        {!isAnalyzing && !showTrends ? (
          <div className="space-y-6">
            <div className="space-y-4">
              <div className="p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                <div className="flex items-center space-x-3 mb-2">
                  <Search className="w-5 h-5 text-blue-400" />
                  <span className="text-white font-medium">Google Trends Tarama</span>
                </div>
                <p className="text-sm text-gray-300">
                  Türkiye'deki güncel trend konuları ve arama hacmi yüksek anahtar kelimeler taranacak.
                </p>
              </div>

              <div className="p-4 bg-purple-500/10 border border-purple-500/20 rounded-lg">
                <div className="flex items-center space-x-3 mb-2">
                  <TrendingUp className="w-5 h-5 text-purple-400" />
                  <span className="text-white font-medium">Trend Analizi</span>
                </div>
                <p className="text-sm text-gray-300">
                  Yükselen konular, arama hacmi ve rekabet seviyesi analiz edilecek.
                </p>
              </div>

              <div className="p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                <div className="flex items-center space-x-3 mb-2">
                  <FileText className="w-5 h-5 text-green-400" />
                  <span className="text-white font-medium">İçerik Önerileri</span>
                </div>
                <p className="text-sm text-gray-300">
                  Trend konulara göre içerik fırsatları ve başlık önerileri sunulacak.
                </p>
              </div>
            </div>

            <div className="flex space-x-3">
              <button 
                onClick={() => setShowAnalysisModal(false)}
                className="flex-1 py-3 px-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
              >
                İptal
              </button>
              <button 
                onClick={startAnalysis}
                className="flex-1 py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2"
              >
                <TrendingUp className="w-4 h-4" />
                <span>Trend Analizini Başlat</span>
              </button>
            </div>
          </div>
        ) : isAnalyzing ? (
          <div className="space-y-6">
            <div className="text-center">
              <div className="w-16 h-16 mx-auto mb-4 relative">
                <div className="w-16 h-16 border-4 border-blue-500/20 rounded-full"></div>
                <div className="w-16 h-16 border-4 border-blue-500 rounded-full border-t-transparent animate-spin absolute top-0 left-0"></div>
              </div>
              <h4 className="text-lg font-semibold text-white mb-2">Trend Analizi Devam Ediyor</h4>
              <p className="text-gray-300 text-sm">Google Trends verileri işleniyor...</p>
            </div>

            <div className="space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-gray-300">İlerleme</span>
                <span className="text-white font-medium">{Math.round(analysisProgress)}%</span>
              </div>
              <div className="w-full bg-gray-700 rounded-full h-2">
                <div 
                  className="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-300"
                  style={{ width: `${analysisProgress}%` }}
                ></div>
              </div>
            </div>

            <div className="p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg">
              <div className="flex items-center space-x-3">
                <RefreshCw className="w-5 h-5 text-blue-400 animate-spin" />
                <span className="text-white text-sm">
                  {analysisProgress < 20 ? 'Google Trends verilerini alıyor...' :
                   analysisProgress < 40 ? 'Trend konularını analiz ediyor...' :
                   analysisProgress < 60 ? 'İçerik fırsatlarını değerlendiriyor...' :
                   analysisProgress < 80 ? 'Anahtar kelime araştırması yapıyor...' :
                   analysisProgress < 95 ? 'Rekabet analizi gerçekleştiriyor...' :
                   'İçerik önerileri hazırlıyor...'}
                </span>
              </div>
            </div>
          </div>
        ) : (
          <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {trendTopics.map((trend) => (
                <div key={trend.id} className="p-4 bg-white/5 border border-white/10 rounded-lg hover:bg-white/10 transition-all duration-300 cursor-pointer group">
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex-1">
                      <div className="flex items-center space-x-2 mb-2">
                        <h4 className="font-semibold text-white group-hover:text-blue-400 transition-colors">{trend.title}</h4>
                        {getTrendIcon(trend.trend)}
                      </div>
                      <div className="flex items-center space-x-3 text-xs text-gray-400 mb-2">
                        <span className="px-2 py-1 bg-blue-500/20 text-blue-400 rounded-full">{trend.category}</span>
                        <span>Arama: {trend.searchVolume}</span>
                      </div>
                      <div className="space-y-1">
                        <p className="text-xs text-gray-400">İlgili aramalar:</p>
                        {trend.relatedQueries.slice(0, 2).map((query, index) => (
                          <span key={index} className="inline-block text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded mr-1 mb-1">
                            {query}
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>
                  <button 
                    onClick={() => generateContentFromTrend(trend)}
                    className="w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2 text-sm"
                  >
                    <FileText className="w-4 h-4" />
                    <span>İçerik Üret</span>
                    <ArrowRight className="w-4 h-4" />
                  </button>
                </div>
              ))}
            </div>
            
            <div className="text-center">
              <button 
                onClick={startAnalysis}
                className="py-2 px-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center space-x-2 mx-auto"
              >
                <RefreshCw className="w-4 h-4" />
                <span>Yeniden Tara</span>
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );

  const renderDashboard = () => (
    <div className="space-y-8">
      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 hover:bg-white/15 transition-all duration-300">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-300 text-sm">Günlük Ziyaretçi</p>
              <p className="text-3xl font-bold text-white">{websiteAnalytics.dailyVisitors.toLocaleString()}</p>
            </div>
            <div className="p-3 bg-blue-500/20 rounded-lg">
              <Users className="w-6 h-6 text-blue-400" />
            </div>
          </div>
          <div className="mt-4 flex items-center text-sm">
            <TrendingUp className="w-4 h-4 text-green-400 mr-1" />
            <span className="text-green-400">+12%</span>
            <span className="text-gray-400 ml-1">dünden</span>
          </div>
        </div>

        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 hover:bg-white/15 transition-all duration-300">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-300 text-sm">Ortalama Süre</p>
              <p className="text-3xl font-bold text-white">{websiteAnalytics.avgSessionDuration}</p>
            </div>
            <div className="p-3 bg-purple-500/20 rounded-lg">
              <Timer className="w-6 h-6 text-purple-400" />
            </div>
          </div>
          <div className="mt-4 flex items-center text-sm">
            <Clock className="w-4 h-4 text-purple-400 mr-1" />
            <span className="text-gray-400">Çıkma oranı: {websiteAnalytics.bounceRate}</span>
          </div>
        </div>

        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 hover:bg-white/15 transition-all duration-300">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-300 text-sm">Yayınlanan</p>
              <p className="text-3xl font-bold text-white">156</p>
            </div>
            <div className="p-3 bg-green-500/20 rounded-lg">
              <Globe className="w-6 h-6 text-green-400" />
            </div>
          </div>
          <div className="mt-4 flex items-center text-sm">
            <BarChart3 className="w-4 h-4 text-green-400 mr-1" />
            <span className="text-green-400">+5</span>
            <span className="text-gray-400 ml-1">bugün</span>
          </div>
        </div>

        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 hover:bg-white/15 transition-all duration-300">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-300 text-sm">SEO Sıralaması</p>
              <p className="text-3xl font-bold text-white">Ort. 6.4</p>
            </div>
            <div className="p-3 bg-orange-500/20 rounded-lg">
              <Target className="w-6 h-6 text-orange-400" />
            </div>
          </div>
          <div className="mt-4 flex items-center text-sm">
            <TrendingUp className="w-4 h-4 text-orange-400 mr-1" />
            <span className="text-orange-400">+2.1</span>
            <span className="text-gray-400 ml-1">haftalık</span>
          </div>
        </div>
      </div>

      {/* Analytics and SEO Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Top Keywords */}
        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-xl font-semibold text-white">En Çok Aranan Kelimeler</h3>
            <Search className="w-5 h-5 text-blue-400" />
          </div>
          <div className="space-y-4">
            {websiteAnalytics.topKeywords.map((keyword, index) => (
              <div key={index} className="flex items-center justify-between">
                <div className="flex-1">
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-white text-sm font-medium">{keyword.keyword}</span>
                    <span className="text-gray-400 text-xs">{keyword.visitors}</span>
                  </div>
                  <div className="w-full bg-gray-700 rounded-full h-2">
                    <div 
                      className="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full"
                      style={{ width: `${keyword.percentage}%` }}
                    ></div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* SEO Rankings */}
        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-xl font-semibold text-white">Anahtar Kelime Sıralaması</h3>
            <Target className="w-5 h-5 text-orange-400" />
          </div>
          <div className="space-y-3">
            {keywordRankings.slice(0, 4).map((ranking) => {
              const change = getRankingChange(ranking.currentPosition, ranking.previousPosition);
              return (
                <div key={ranking.id} className="p-3 bg-white/5 rounded-lg">
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-white text-sm font-medium">{ranking.keyword}</span>
                    <div className="flex items-center space-x-2">
                      <span className="text-white font-bold">#{ranking.currentPosition}</span>
                      <div className={`flex items-center space-x-1 ${change.color}`}>
                        {change.icon}
                        {change.change > 0 && <span className="text-xs">{change.change}</span>}
                      </div>
                    </div>
                  </div>
                  <div className="flex items-center justify-between text-xs text-gray-400">
                    <span>{ranking.searchVolume}/ay</span>
                    <span className={`px-2 py-1 rounded-full ${getDifficultyColor(ranking.difficulty)}`}>
                      {ranking.difficulty}
                    </span>
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Traffic Sources */}
        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-xl font-semibold text-white">Trafik Kaynakları</h3>
            <MousePointer className="w-5 h-5 text-green-400" />
          </div>
          <div className="space-y-4">
            {websiteAnalytics.trafficSources.map((source, index) => (
              <div key={index} className="flex items-center justify-between">
                <div className="flex-1">
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-white text-sm font-medium">{source.source}</span>
                    <span className="text-gray-400 text-xs">{source.visitors}</span>
                  </div>
                  <div className="w-full bg-gray-700 rounded-full h-2">
                    <div 
                      className={`h-2 rounded-full ${
                        index === 0 ? 'bg-green-500' :
                        index === 1 ? 'bg-blue-500' :
                        index === 2 ? 'bg-purple-500' :
                        'bg-orange-500'
                      }`}
                      style={{ width: `${source.percentage}%` }}
                    ></div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Recent Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-xl font-semibold text-white">Son Haberler</h3>
            <button 
              onClick={() => setShowAnalysisModal(true)}
              className="p-2 bg-blue-500/20 rounded-lg hover:bg-blue-500/30 transition-colors"
            >
              <Plus className="w-4 h-4 text-blue-400" />
            </button>
          </div>
          <div className="space-y-4">
            {newsItems.slice(0, 4).map((item) => (
              <div key={item.id} className="flex items-center justify-between p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors">
                <div className="flex-1">
                  <h4 className="font-medium text-white text-sm mb-1">{item.title}</h4>
                  <div className="flex items-center text-xs text-gray-400 space-x-3">
                    <span>{item.source}</span>
                    <span>{item.publishedAt}</span>
                    <span className={`flex items-center space-x-1 ${getStatusColor(item.status)}`}>
                      {getStatusIcon(item.status)}
                      <span className="capitalize">{item.status}</span>
                    </span>
                  </div>
                </div>
                <div className="flex space-x-2">
                  <button className="p-1.5 bg-blue-500/20 rounded hover:bg-blue-500/30 transition-colors">
                    <Eye className="w-3 h-3 text-blue-400" />
                  </button>
                  <button className="p-1.5 bg-green-500/20 rounded hover:bg-green-500/30 transition-colors">
                    <PlayCircle className="w-3 h-3 text-green-400" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-xl font-semibold text-white">En Popüler Sayfalar</h3>
            <BarChart3 className="w-5 h-5 text-purple-400" />
          </div>
          <div className="space-y-4">
            {websiteAnalytics.topPages.map((page, index) => (
              <div key={index} className="p-4 bg-white/5 rounded-lg hover:bg-white/10 transition-colors">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <h4 className="font-medium text-white text-sm mb-2">{page.page}</h4>
                    <div className="flex items-center text-xs text-gray-400 space-x-3">
                      <span className="flex items-center space-x-1">
                        <Users className="w-3 h-3" />
                        <span>{page.visitors} ziyaretçi</span>
                      </span>
                      <span className="flex items-center space-x-1">
                        <Timer className="w-3 h-3" />
                        <span>{page.avgDuration}</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );

  const renderNewsAnalysis = () => (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold text-white">Haber Analizi ve İşleme</h2>
        <button 
          onClick={() => setShowAnalysisModal(true)}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
        >
          <TrendingUp className="w-4 h-4" />
          <span>Trend Analizi</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {newsItems.map((item) => (
          <div key={item.id} className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-4 hover:bg-white/15 transition-all duration-300">
            <div className="flex items-start justify-between mb-3">
              <div className="flex-1">
                <div className="flex items-center space-x-2 mb-2">
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                    item.category === 'AI' ? 'bg-blue-500/20 text-blue-400' :
                    item.category === 'Blockchain' ? 'bg-purple-500/20 text-purple-400' :
                    item.category === 'Hardware' ? 'bg-green-500/20 text-green-400' :
                    item.category === 'VR/AR' ? 'bg-pink-500/20 text-pink-400' :
                    item.category === 'Quantum' ? 'bg-orange-500/20 text-orange-400' :
                    item.category === 'Automotive' ? 'bg-red-500/20 text-red-400' :
                    'bg-gray-500/20 text-gray-400'
                  }`}>
                    {item.category}
                  </span>
                  <span className={`flex items-center space-x-1 text-xs ${getStatusColor(item.status)}`}>
                    {getStatusIcon(item.status)}
                    <span className="capitalize">{item.status}</span>
                  </span>
                </div>
                <h3 className="text-base font-semibold text-white mb-2 line-clamp-2">{item.title}</h3>
                <div className="flex items-center text-xs text-gray-400 space-x-3">
                  <span>{item.source}</span>
                  <span>{item.publishedAt}</span>
                </div>
              </div>
            </div>
            
            <div className="flex space-x-2 mt-3">
              <button className="flex-1 py-2 px-3 bg-blue-500/20 rounded-lg hover:bg-blue-500/30 transition-colors text-xs text-blue-400 flex items-center justify-center space-x-1">
                <Eye className="w-3 h-3" />
                <span>Görüntüle</span>
              </button>
              <button className="flex-1 py-2 px-3 bg-green-500/20 rounded-lg hover:bg-green-500/30 transition-colors text-xs text-green-400 flex items-center justify-center space-x-1">
                <Edit className="w-3 h-3" />
                <span>Düzenle</span>
              </button>
              <button className="flex-1 py-2 px-3 bg-purple-500/20 rounded-lg hover:bg-purple-500/30 transition-colors text-xs text-purple-400 flex items-center justify-center space-x-1">
                <Calendar className="w-3 h-3" />
                <span>Zamanla</span>
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  const renderScheduling = () => (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold text-white">İçerik Zamanlama</h2>
        <button className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center space-x-2">
          <Plus className="w-4 h-4" />
          <span>Yeni Zamanlama</span>
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="col-span-2">
          <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
            <h3 className="text-lg font-semibold text-white mb-4">Zamanlanmış Gönderiler</h3>
            <div className="space-y-4">
              {scheduledPosts.map((post) => (
                <div key={post.id} className="p-4 bg-white/5 rounded-lg border-l-4 border-purple-500 hover:bg-white/10 transition-colors">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <h4 className="font-medium text-white mb-2">{post.title}</h4>
                      <p className="text-sm text-gray-300 mb-3 line-clamp-2">{post.content}</p>
                      <div className="flex items-center text-xs text-gray-400 space-x-4">
                        <span className="flex items-center space-x-1">
                          <Clock className="w-3 h-3" />
                          <span>{post.scheduledTime}</span>
                        </span>
                        <span className={`capitalize ${getStatusColor(post.status)}`}>
                          {post.status}
                        </span>
                        <span className={`px-2 py-1 rounded-full text-xs ${
                          post.category === 'AI' ? 'bg-blue-500/20 text-blue-400' :
                          'bg-green-500/20 text-green-400'
                        }`}>
                          {post.category}
                        </span>
                      </div>
                    </div>
                    <div className="flex space-x-2">
                      <button className="p-2 bg-blue-500/20 rounded-lg hover:bg-blue-500/30 transition-colors">
                        <Edit className="w-4 h-4 text-blue-400" />
                      </button>
                      <button className="p-2 bg-green-500/20 rounded-lg hover:bg-green-500/30 transition-colors">
                        <PlayCircle className="w-4 h-4 text-green-400" />
                      </button>
                      <button className="p-2 bg-red-500/20 rounded-lg hover:bg-red-500/30 transition-colors">
                        <Trash2 className="w-4 h-4 text-red-400" />
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="space-y-6">
          <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
            <h3 className="text-lg font-semibold text-white mb-4">Yayın Takvimi</h3>
            <div className="space-y-3">
              <div className="p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm font-medium text-white">Bugün</span>
                  <span className="text-xs text-blue-400">3 gönderi</span>
                </div>
                <div className="text-xs text-gray-400">18:00, 20:00, 22:00</div>
              </div>
              <div className="p-3 bg-purple-500/10 border border-purple-500/20 rounded-lg">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm font-medium text-white">Yarın</span>
                  <span className="text-xs text-purple-400">2 gönderi</span>
                </div>
                <div className="text-xs text-gray-400">10:00, 16:00</div>
              </div>
              <div className="p-3 bg-green-500/10 border border-green-500/20 rounded-lg">
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm font-medium text-white">Bu Hafta</span>
                  <span className="text-xs text-green-400">12 gönderi</span>
                </div>
                <div className="text-xs text-gray-400">Günlük ortalama: 2.4</div>
              </div>
            </div>
          </div>

          <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
            <h3 className="text-lg font-semibold text-white mb-4">Otomatik Ayarlar</h3>
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-sm text-gray-300">Günlük yayın sayısı</span>
                <span className="text-sm text-white font-medium">3-5</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-gray-300">En iyi yayın saatleri</span>
                <span className="text-sm text-white font-medium">09:00-21:00</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-gray-300">AI analiz aktif</span>
                <div className="w-8 h-4 bg-green-500 rounded-full flex items-center justify-end px-1">
                  <div className="w-3 h-3 bg-white rounded-full"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderSettings = () => (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold text-white">Sistem Ayarları</h2>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
          <h3 className="text-lg font-semibold text-white mb-4">WordPress Bağlantısı</h3>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Site URL</label>
              <input 
                type="url" 
                className="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="https://example.com"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">API Anahtarı</label>
              <input 
                type="password" 
                className="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="WordPress API anahtarınız"
              />
            </div>
            <button className="w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              Bağlantıyı Test Et
            </button>
          </div>
        </div>

        <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
          <h3 className="text-lg font-semibold text-white mb-4">Haber Kaynakları</h3>
          <div className="space-y-3">
            {['TechCrunch', 'Wired', 'The Verge', 'Ars Technica', 'Engadget'].map((source) => (
              <div key={source} className="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                <span className="text-white">{source}</span>
                <div className="w-8 h-4 bg-green-500 rounded-full flex items-center justify-end px-1">
                  <div className="w-3 h-3 bg-white rounded-full"></div>
                </div>
              </div>
            ))}
          </div>
          <button className="w-full mt-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            Yeni Kaynak Ekle
          </button>
        </div>
      </div>

      {/* SEO Keyword Tracking */}
      <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-lg font-semibold text-white">Anahtar Kelime Takibi</h3>
          <button className="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors flex items-center space-x-2">
            <Plus className="w-4 h-4" />
            <span>Yeni Kelime Ekle</span>
          </button>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {keywordRankings.map((ranking) => {
            const change = getRankingChange(ranking.currentPosition, ranking.previousPosition);
            return (
              <div key={ranking.id} className="p-4 bg-white/5 rounded-lg border border-white/10">
                <div className="flex items-center justify-between mb-3">
                  <h4 className="font-medium text-white text-sm">{ranking.keyword}</h4>
                  <div className="flex items-center space-x-2">
                    <span className="text-white font-bold text-lg">#{ranking.currentPosition}</span>
                    <div className={`flex items-center space-x-1 ${change.color}`}>
                      {change.icon}
                      {change.change > 0 && <span className="text-xs">{change.change}</span>}
                    </div>
                  </div>
                </div>
                <div className="space-y-2">
                  <div className="flex items-center justify-between text-xs text-gray-400">
                    <span>Arama Hacmi</span>
                    <span className="text-white">{ranking.searchVolume}/ay</span>
                  </div>
                  <div className="flex items-center justify-between text-xs text-gray-400">
                    <span>Zorluk</span>
                    <span className={`px-2 py-1 rounded-full ${getDifficultyColor(ranking.difficulty)}`}>
                      {ranking.difficulty}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs text-gray-400">
                    <span>URL</span>
                    <span className="text-blue-400 truncate max-w-24">{ranking.url}</span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Website Analytics */}
      <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
        <h3 className="text-lg font-semibold text-white mb-6">Web Sitesi Durumu</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {/* Daily Stats */}
          <div className="space-y-4">
            <h4 className="text-white font-medium">Günlük İstatistikler</h4>
            <div className="space-y-3">
              <div className="p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                <div className="flex items-center justify-between">
                  <span className="text-gray-300 text-sm">Toplam Ziyaretçi</span>
                  <span className="text-white font-bold">{websiteAnalytics.dailyVisitors.toLocaleString()}</span>
                </div>
              </div>
              <div className="p-3 bg-purple-500/10 border border-purple-500/20 rounded-lg">
                <div className="flex items-center justify-between">
                  <span className="text-gray-300 text-sm">Ortalama Süre</span>
                  <span className="text-white font-bold">{websiteAnalytics.avgSessionDuration}</span>
                </div>
              </div>
              <div className="p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
                <div className="flex items-center justify-between">
                  <span className="text-gray-300 text-sm">Çıkma Oranı</span>
                  <span className="text-white font-bold">{websiteAnalytics.bounceRate}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Top Search Terms */}
          <div className="space-y-4">
            <h4 className="text-white font-medium">Arama Terimleri</h4>
            <div className="space-y-2">
              {websiteAnalytics.topKeywords.map((keyword, index) => (
                <div key={index} className="p-2 bg-white/5 rounded-lg">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-gray-300">{keyword.keyword}</span>
                    <span className="text-white font-medium">{keyword.visitors}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Session Duration by Page */}
          <div className="space-y-4">
            <h4 className="text-white font-medium">Sayfa Performansı</h4>
            <div className="space-y-2">
              {websiteAnalytics.topPages.map((page, index) => (
                <div key={index} className="p-2 bg-white/5 rounded-lg">
                  <div className="text-sm text-gray-300 mb-1">{page.page}</div>
                  <div className="flex items-center justify-between text-xs text-gray-400">
                    <span>{page.visitors} ziyaretçi</span>
                    <span className="text-white font-medium">{page.avgDuration}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
        <h3 className="text-lg font-semibold text-white mb-4">AI ve Otomasyon Ayarları</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-gray-300">Otomatik haber toplama</span>
              <div className="w-8 h-4 bg-green-500 rounded-full flex items-center justify-end px-1">
                <div className="w-3 h-3 bg-white rounded-full"></div>
              </div>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-gray-300">AI içerik analizi</span>
              <div className="w-8 h-4 bg-green-500 rounded-full flex items-center justify-end px-1">
                <div className="w-3 h-3 bg-white rounded-full"></div>
              </div>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-gray-300">Otomatik yayınlama</span>
              <div className="w-8 h-4 bg-blue-500 rounded-full flex items-center justify-start px-1">
                <div className="w-3 h-3 bg-white rounded-full"></div>
              </div>
            </div>
          </div>
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Günlük maksimum gönderi</label>
              <input 
                type="number" 
                className="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                defaultValue="5"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Minimum analiz skoru</label>
              <input 
                type="range" 
                min="0" 
                max="100" 
                defaultValue="75"
                className="w-full"
              />
              <div className="flex justify-between text-xs text-gray-400 mt-1">
                <span>0</span>
                <span>50</span>
                <span>100</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: BarChart3 },
    { id: 'news', label: 'Haber Analizi', icon: FileText },
    { id: 'schedule', label: 'Zamanlama', icon: Calendar },
    { id: 'settings', label: 'Ayarlar', icon: Settings },
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
      <div className="flex">
        {/* Sidebar */}
        <div className="w-64 min-h-screen bg-black/20 backdrop-blur-sm border-r border-white/10">
          <div className="p-6">
            <div className="flex items-center space-x-3 mb-8">
              <div className="p-2 bg-blue-600 rounded-lg">
                <Zap className="w-6 h-6 text-white" />
              </div>
              <div>
                <h1 className="text-xl font-bold text-white">NewsBot</h1>
                <p className="text-xs text-gray-400">WP Otomasyon</p>
              </div>
            </div>
            
            <nav className="space-y-2">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-300 ${
                    activeTab === tab.id
                      ? 'bg-blue-600 text-white'
                      : 'text-gray-300 hover:bg-white/10 hover:text-white'
                  }`}
                >
                  <tab.icon className="w-5 h-5" />
                  <span className="font-medium">{tab.label}</span>
                </button>
              ))}
            </nav>
          </div>
        </div>

        {/* Main Content */}
        <div className="flex-1 p-8">
          <div className="max-w-7xl mx-auto">
            {activeTab === 'dashboard' && renderDashboard()}
            {activeTab === 'news' && renderNewsAnalysis()}
            {activeTab === 'schedule' && renderScheduling()}
            {activeTab === 'settings' && renderSettings()}
          </div>
        </div>
      </div>

      {/* Analysis Modal */}
      {showAnalysisModal && <AnalysisModal />}
    </div>
  );
}

export default App;