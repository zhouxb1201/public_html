<template>
  <Layout ref="load" class="bonus-level bg-f8">
    <Navbar :title="navbarTitle" />
    <LevelCard
      :name="user_info.user_name"
      :img="user_info.user_headimg"
      :text="user_info.level_name"
    />
    <LevelTable
      title="代理地区"
      :title_list="agent_area_table.title_list"
      :items="agent_area_table.data"
      v-if="pagetype == 2"
      class="area-wrap"
    />
    <LevelTable :title_list="equity_title_list" :items="equity_data" />
    <LevelUpgrade :info="upgrade" />
    <LevelDemote :info="demote" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import LevelCard from "@/components/LevelCard";
import LevelTable from "@/components/LevelTable";
import LevelUpgrade from "./component/LevelUpgrade";
import LevelDemote from "../commission/component/LevelDemote";
export default sfc({
  name: "bonus-level",
  data() {
    return {
      user_info: {},
      equity_data: [],
      agent_area_table: {
        title_list: ["地区", "分红比例"],
        data: []
      },

      upgrade: {},
      demote: {}
    };
  },
  computed: {
    pagetype() {
      return this.$route.params.pagetype;
    },
    navbarTitle() {
      let title = "";
      if (this.pagetype == 1) {
        title = "团队队长等级详情";
      } else if (this.pagetype == 2) {
        title = "区域代理等级详情";
      } else if (this.pagetype == 3) {
        title = "全球股东等级详情";
      }
      return title;
    },
    equity_title_list() {
      let title_list = [];
      if (this.pagetype == 1 || this.pagetype == 3) {
        title_list = ["等级", "分红比例"];
      } else {
        title_list = ["等级", "省分红", "市分红", "区分红"];
      }
      return title_list;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      $this.$store
        .dispatch("getUpbonusLevelInfo", {
          types: $this.pagetype
        })
        .then(data => {
          $this.user_info = data.user;
          $this.upgrade = data.levelCondition;
          $this.demote = data.downlevelCondition;
          //等级权益Table
          data.levels.forEach(e => {
            let level_list = {};
            level_list.row1 = e.level_name;
            if ($this.pagetype == 1 || $this.pagetype == 3) {
              level_list.row2 = parseFloat(e.ratio)
                ? parseFloat(e.ratio) + "%"
                : "--";
            } else {
              level_list.row2 = parseFloat(e.province_ratio)
                ? parseFloat(e.province_ratio) + "%"
                : "--";
              level_list.row3 = parseFloat(e.city_ratio)
                ? parseFloat(e.city_ratio) + "%"
                : "--";

              level_list.row4 = parseFloat(e.area_ratio)
                ? parseFloat(e.area_ratio) + "%"
                : "--";
            }
            $this.equity_data.push(level_list);
          });
          //代理地区Table
          if ($this.pagetype == 2) {
            data.user.area_data.forEach(e => {
              let list = {};
              list.row1 = e.area_name;
              list.row2 = parseFloat(e.area_ratio)
                ? parseFloat(e.area_ratio) + "%"
                : "--";
              $this.agent_area_table.data.push(list);
            });
          }
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.success();
        });
    }
  },
  components: {
    LevelCard,
    LevelTable,
    LevelUpgrade,
    LevelDemote
  }
});
</script>

<style scoped>
.area-wrap {
  position: relative;
  margin-bottom: 10px;
}
.area-wrap >>> .level-left {
  width: 75%;
}
.area-wrap >>> .level-right {
  width: 25%;
}
</style>
