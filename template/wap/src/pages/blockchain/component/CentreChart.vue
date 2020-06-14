<template>
  <div class="cahrt-box">
    <van-tabs v-model="active" @change="onChange">
      <van-tab :title="item.text" v-for="(item,index) in tabs" :key="index" />
    </van-tabs>
    <e-chart class="chart" ref="line" :style="{height:docHeight}" autoresize :options="option" />
  </div>
</template>

<script>
import ECharts from "vue-echarts";
import "echarts/lib/chart/line";
import "echarts/lib/component/title";
import "echarts/lib/component/dataZoom";
import "echarts/lib/component/tooltip";

import { GET_BLOCKCHAINCHART } from "@/api/blockchain";
import { isEmpty } from "@/utils/util";
export default {
  data() {
    return {
      active: 0,
      tabs: [
        {
          text: "分钟",
          type: "minute"
        },
        {
          text: "小时",
          type: "hour"
        },
        {
          text: "天",
          type: "day"
        }
      ],
      timeType: "minute",

      loading: true,

      option: {}
    };
  },
  props: {
    type: String
  },
  computed: {
    docHeight() {
      return document.body.offsetWidth / 1.5 + "px";
    }
  },
  mounted() {
    this.changeChart();
  },
  methods: {
    onChange(e) {
      this.timeType = this.tabs[e].type;
      this.changeChart();
    },
    changeChart() {
      const line = this.$refs.line;
      line.showLoading({
        text: "加载中",
        color: "#4ea397",
        maskColor: "rgba(255, 255, 255, 0.4)"
      });
      this.option = {};
      GET_BLOCKCHAINCHART(this.type, this.timeType)
        .then(({ data }) => {
          this.formatData(data);
          line.hideLoading();
        })
        .catch(() => {
          line.hideLoading();
        });
    },
    formatData({ list }) {
      let date = [];
      let data = [];
      list.forEach(({ timestamp, price }) => {
        date.push(timestamp);
        data.push(price);
      });
      this.option = {
        tooltip: {
          trigger: "axis",
          position: ["35%", "80%"],
          hideDelay: 500 // 隐藏延迟
        },
        title: {
          left: "center",
          text: this.type.toUpperCase() + "行情走势"
        },
        toolbox: {
          feature: {
            dataZoom: {
              yAxisIndex: "none"
            },
            restore: {},
            saveAsImage: {}
          }
        },
        xAxis: {
          type: "category",
          boundaryGap: false,
          boundaryGap: [0, "100%"],
          data: date
        },
        yAxis: {
          position: "right",
          scale: true,
          type: "value",
          splitNumber: 5,

          boundaryGap: false,
          dataaxisLabel: {
            margin: 0,
            formatter: function(value, index) {
              if (value >= 10000 && value < 10000000) {
                value = value / 10000 + "万";
              } else if (value >= 10000000) {
                value = value / 10000000 + "千万";
              }
              return value;
            }
          }
        },
        grid: {
          right: 60
        },
        dataZoom: [
          {
            type: "slider",
            start: 80,
            end: 100
          },
          {
            start: 80,
            end: 100,
            handleIcon:
              "M10.7,11.9v-1.3H9.3v1.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4v1.3h1.3v-1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z",
            handleSize: "100%",
            handleStyle: {
              color: "#fff",
              shadowBlur: 3,
              shadowColor: "rgba(0, 0, 0, 0.6)",
              shadowOffsetX: 0,
              shadowOffsetY: 0
            }
          }
        ],
        series: [
          {
            name: "价格",
            type: "line",
            smooth: true,
            symbol: "rect",
            sampling: "average",
            itemStyle: {
              color: "rgb(255,255,255)",
              normal: {
                color: "rgb(255, 69, 78)",
                label: {
                  show: false,
                  position: "insert"
                }
              }
            },
            areaStyle: {
              color: new ECharts.graphic.LinearGradient(0, 0, 0, 1, [
                {
                  offset: 0,
                  color: "rgb(0,200,255)"
                },
                {
                  offset: 1,
                  color: "rgb(0,255,255)"
                }
              ])
            },
            data: data
          }
        ]
      };

      this.loading = false;
    }
  },
  components: {
    "e-chart": ECharts
  }
};
</script>

<style scoped>
.chart {
  width: 100%;
  height: 320px;
}
.cahrt-box {
  margin: 10px 0;
  background: #ffffff;
}
</style>
