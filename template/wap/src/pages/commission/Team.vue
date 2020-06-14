<template>
  <div class="commission-team bg-f8">
    <Navbar :title="navbarTitle" />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell-group>
        <van-cell class="item" v-for="(item, index) in list" :key="index">
          <div class="img" slot="icon">
            <img
              v-lazy="item.user_headimg"
              :key="item.user_headimg"
              pic-type="square"
            />
          </div>
          <div class="info">
            <div class="box">
              <div class="name">{{ item.member_name }}</div>
              <div class="level-name">{{ item.distributor_level_name }}</div>
            </div>
            <div class="box">
              <div class="">团队三级内:</div>
              <div class="">{{ item.agentcount || 0 }}人</div>
            </div>
            <div class="box">
              <div class="">团队所有:</div>
              <div class="">{{ item.all_child || 0 }}人</div>
            </div>
            <div class="box">
              <div class="">下线客户:</div>
              <div class="">{{ item[user_num(params.type)] || 0 }}人</div>
            </div>
          </div>
          <div slot="right-icon" class="commission-text">
            {{ $store.state.member.commissionSetText.commission }}:
            <span>{{ item.commission ? item.commission : 0 }}</span
            >元
          </div>
        </van-cell>
      </van-cell-group>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import { GET_TEAMLIST } from "@/api/commission";
import { list } from "@/mixins";
export default sfc({
  name: "commission-team",
  data() {
    return {
      tab_active: 0,
      params: {
        type: 1
      },
      teamCount: {
        team1: 0,
        team2: 0,
        team3: 0
      },
      distribution_pattern: 0
    };
  },
  mixins: [list],
  computed: {
    navbarTitle() {
      const { my_team } = this.$store.state.member.commissionSetText;
      let title = my_team;
      document.title = title;
      return title;
    },
    tabs() {
      const {
        team1,
        team2,
        team3
      } = this.$store.state.member.commissionSetText;
      let arr = [];
      for (let i = 1; i <= this.distribution_pattern; i++) {
        if (i == 1)
          arr.push({ name: team1 + "(" + this.teamCount.team1 + ")", type: 1 });
        if (i == 2)
          arr.push({ name: team2 + "(" + this.teamCount.team2 + ")", type: 2 });
        if (i == 3)
          arr.push({ name: team3 + "(" + this.teamCount.team3 + ")", type: 3 });
      }
      return arr;
    }
  },
  mounted() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    this.$store.dispatch("getCommissionInfo").then(data => {
      this.distribution_pattern = data.distribution_pattern;
      this.loadList();
    });
  },
  methods: {
    onTab(index) {
      const $this = this;
      const type = $this.tabs[index].type;
      $this.params.type = type;
      $this.loadList("init");
    },
    user_num(num){
      let obj = {
        1:'user_count',
        2:'user_count2',
        3:'user_count3',
      }
      return obj[num]
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_TEAMLIST($this.params)
        .then(({ data }) => {
          let list = data.data ? data.data : [];
          this.teamCount = {
            team1: data.number1 || 0,
            team2: data.number2 || 0,
            team3: data.number3 || 0
          };
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    HeadTab
  }
});
</script>

<style scoped>
.item .box {
  display: flex;
  font-size: 12px;
  line-height: 20px
}
.item .box .name{
  color: #323233;
}
.item .img {
  width: 60px;
  height: 60px;
  margin-right: 10px;
}

.item .img img {
  display: block;
  width: 100%;
  height: 100%;
}

.item .info {
  flex: 1;
}

.item .info .level-name {
  padding-left: 10px;
  color: #606266;
  font-size: 12px;
}

.commission-text {
  padding-left: 10px;
  color: #606266;
  font-size: 12px;
}
</style>
