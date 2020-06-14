<template>
  <Layout ref="load" class="commission-ranking bg-f8">
    <Navbar />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <RankingGroup :list="list" @change="onRankChange" />
    <div class="foot" v-if="info.ranking">
      <div class="info-left">
        <div class="num">{{info.ranking}}</div>
        <div class="img">
          <img :src="info.user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar" />
        </div>
        <div class="name">{{info.nick_name}}</div>
      </div>
      <div class="info-right">
        <span class="total">{{info.number}}{{info.unit}}</span>
      </div>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import RankingGroup from "./component/RankingGroup";
import { GET_COMMISSIONRANKING } from "@/api/commission";
export default sfc({
  name: "commission-ranking",
  data() {
    return {
      tab_active: 0,
      list: [],
      info: {},
      params: {
        types: 1,
        times: "month",
        psize: 10
      }
    };
  },
  computed: {
    tabs() {
      const { commission } = this.$store.state.member.commissionSetText;
      const { point_style } = this.$store.state.member.memberSetText;
      return [
        {
          name: "推荐榜",
          types: 1,
          unit: "人",
          number: "total"
        },
        {
          name: `${commission}榜`,
          types: 2,
          unit: `${commission}`,
          number: "commissions"
        },
        {
          name: `${point_style}榜`,
          types: 3,
          unit: `${point_style}`,
          number: "points"
        }
      ];
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      GET_COMMISSIONRANKING(this.params)
        .then(({ data }) => {
          const { number, unit } = this.tabs[this.tab_active];
          this.list = data.rankinglists.map(item => {
            item.number = item[number];
            item.unit = unit;
            return item;
          });
          this.info = data.user;
          this.info.unit = unit;
          this.info.number = data.user[number];
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    onTab(e) {
      this.tab_active = e;
      this.params.types = this.tabs[e].types;
      this.loadData();
    },
    onRankChange(times) {
      this.params.times = times;
      this.loadData();
    }
  },
  components: {
    HeadTab,
    RankingGroup
  }
});
</script>

<style scoped>
.foot {
  width: 100%;
  position: fixed;
  bottom: 0;
  height: 50px;
  display: flex;
  justify-content: space-between;
  background: #ff454e;
  color: #fff;
  padding: 0 15px;
  align-items: center;
}

.info-left {
  display: flex;
  align-items: center;
}

.info-left .num {
  font-size: 18px;
  font-weight: 800;
  width: 40px;
  text-align: center;
}

.info-left .img {
  width: 40px;
  height: 40px;
  margin: 0 10px;
}

.info-left .img img {
  border-radius: 50%;
  width: 100%;
  height: 100%;
  display: block;
}
</style>
